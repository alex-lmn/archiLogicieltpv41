<?php
// src/Controller/ArticleController.php
namespace App\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class ArticleController extends AbstractController
{
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $donnees = $this->getDoctrine()->getRepository(Article::class)->findAll();

        $articles = $paginator->paginate(
            $donnees, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, 1 par défaut
            6 // Nombre de résultats par page
        );

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
        ]);
    }
}
?>