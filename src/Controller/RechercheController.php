<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Catalogue\Article;
use Knp\Component\Pager\PaginatorInterface;

class RechercheController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }
    
    #[Route('/afficheRecherche', name: 'afficheRecherche')]
    public function afficheRechercheAction(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $this->entityManager->createQuery("SELECT a FROM App\Entity\Catalogue\Article a");
        $articles = $query->getResult();
        $pagination = $paginator->paginate(
            $articles,
            $request->query->getInt('page', 1),
            30 /*limit de la page*/
        );
        
        return $this->render('recherche.html.twig', [
            'pagination' => $pagination,
        ]);
    }
    
    #[Route('/afficheRechercheParMotCle', name: 'afficheRechercheParMotCle')]
    public function afficheRechercheParMotCleAction(Request $request, PaginatorInterface $paginator): Response
    {
        $motCle = $request->query->get('motCle');
        $query = $this->entityManager->createQuery("SELECT a FROM App\Entity\Catalogue\Article a WHERE a.titre LIKE :motCle");
        $query->setParameter("motCle", "%".$motCle."%");
        $articles = $query->getResult();
        $pagination = $paginator->paginate(
            $articles,
            $request->query->getInt('page', 1),
            30 /*limite par page*/
        );
        return $this->render('recherche.html.twig', [
            'pagination' => $pagination,
        ]);
    }
}
