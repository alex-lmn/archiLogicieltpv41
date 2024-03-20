<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Psr\Log\LoggerInterface;

use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Catalogue\Musique;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class AdminController extends AbstractController
{
	private EntityManagerInterface $entityManager;
	private LoggerInterface $logger;

	public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
	{
		$this->entityManager = $entityManager;
		$this->logger = $logger;
	}

	#[Route('/admin/musiques', name: 'adminMusiques')]
	public function adminMusiquesAction(Request $request): Response
	{
		$query = $this->entityManager->createQuery("SELECT a FROM App\Entity\Catalogue\Musique a");
		$articles = $query->getResult();
		return $this->render('admin.musiques.html.twig', [
			'articles' => $articles,
		]);
	}

	#[Route('/admin/musiques/supprimer', name: 'adminMusiquesSupprimer')]
	public function adminMusiquesSupprimerAction(Request $request): Response
	{
		$entityArticle = $this->entityManager->getReference("App\Entity\Catalogue\Article", $request->query->get("id"));
		if ($entityArticle !== null) {
			$this->entityManager->remove($entityArticle);
			$this->entityManager->flush();
		}
		return $this->redirectToRoute("adminMusiques");
	}

	#[Route('/admin/musiques/ajouter', name: 'adminMusiquesAjouter')]
	public function adminMusiquesAjouterAction(Request $request): Response
	{
		$entity = new Musique();
		$formBuilder = $this->createFormBuilder($entity);
		$formBuilder->add("titre", TextType::class);
		$formBuilder->add("artiste", TextType::class);
		$formBuilder->add("prix", NumberType::class);
		$formBuilder->add("disponibilite", IntegerType::class);
		$formBuilder->add("image", TextType::class);
		$formBuilder->add("dateDeParution", TextType::class);
		$formBuilder->add("valider", SubmitType::class);
		// Generate form
		$form = $formBuilder->getForm();

		$form->handleRequest($request);

		if ($form->isSubmitted()) {
			$entity = $form->getData();
			$entity->setId(hexdec(uniqid()));
			$this->entityManager->persist($entity);
			$this->entityManager->flush();
			return $this->redirectToRoute("adminMusiques");
		} else {
			return $this->render('admin.form.html.twig', [
				'form' => $form->createView(),
			]);
		}
	}

	#[Route('/admin/musiques/modifier', name: 'adminMusiquesModifier')]
	public function adminMusiquesModifierAction(Request $request): Response
	{
		$entity = $this->entityManager->getReference("App\Entity\Catalogue\Musique", $request->query->get("id"));
		if ($entity === null)
			$entity = $this->entityManager->getReference("App\Entity\Catalogue\Musique", $request->request->get("id"));
		if ($entity !== null) {
			$formBuilder = $this->createFormBuilder($entity);
			$formBuilder->add("id", HiddenType::class);
			$formBuilder->add("titre", TextType::class);
			$formBuilder->add("artiste", TextType::class);
			$formBuilder->add("prix", NumberType::class);
			$formBuilder->add("disponibilite", IntegerType::class);
			$formBuilder->add("image", TextType::class);
			$formBuilder->add("dateDeParution", DateType::class, [
				'widget' => 'single_text',
				'format' => 'yyyy-MM-dd', // Format de date attendu
			]);
			$formBuilder->add("valider", SubmitType::class);
			// Generate form
			$form = $formBuilder->getForm();

			$form->handleRequest($request);

			if ($form->isSubmitted()) {
				$entity = $form->getData();
				$this->entityManager->persist($entity);
				$this->entityManager->flush();
				return $this->redirectToRoute("adminMusiques");
			} else {
				return $this->render('admin.form.html.twig', [
					'form' => $form->createView(),
				]);
			}
		} else {
			return $this->redirectToRoute("adminMusiques");
		}
	}
}
