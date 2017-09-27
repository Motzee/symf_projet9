<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\Article ;
use AppBundle\Form\ArticleType ;




class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }
    
    /**
     * @Route("/test", name="testy")
     */
    public function testAction()
    {
        // génération d'un nombre aléatoire
        $letest = "Hey, ça marche toujours !!!";
        //ici on va chercher le template et on lui transmet la variable
        return $this->render('AppBundle:Default:test.html.twig', array(
            // pour fournir des variables au template
            // a gauche, le nom qui sera utilisé dans le template
            // a droite, la valeur
            'test' => $letest
    ));
    }
    
    /**
     * //CECI EST DU CODE !!!! C'est une annotation PHP significative pour symfony 
     * @Route("/lucky/number/{max}",
     *          name="lucky_number",
     *          defaults={"max":100},
     *          requirements={"max" : "\d+"}
     *  )
     * 
     * //Commentaire : on peut voir le résultat à l'url suivante: http://localhost:8000/lucky/number/100
     *  defaults permet de définir une valeur par défaut si l'utilisateur (prob de config : ne pas avoir de / à la fin de l'url)
     *  requirements permet de faire du typage
     */
    public function numberAction($max)
    {
        // génération d'un nombre aléatoire
        $number = mt_rand(0, $max);
        //ici on va chercher le template et on lui transmet la variable
        return $this->render('AppBundle:Default:number.html.twig', array(
            // pour fournir des variables au template
            // a gauche, le nom qui sera utilisé dans le template
            // a droite, la valeur
            'number' => $number
        ));
    }
    
    
    
    /**
     * //route spéciale variable d'url _locale facultative
     * @Route("/blog/{year}/{title}",
     *          defaults={"_locale":"fr"},
     *          requirements={
     *              "year" : "\d{4}",
     *              "title" : "[a-zA-Z0-9-]+"
     *          }
     *  )
     * 
     * //autres cas
     * @Route("/blog/{_locale}/{year}/{title}",
     *          name="blog",
     *          requirements={
     *              "_locale" : "fr|en",
     *              "year" : "\d{4}",
     *              "title" : "[a-zA-Z0-9-]+"
     *          }
     *  )
     * 
     */
    public function blogAction($_locale, $year, $title)
    {
        //ici on va chercher le template et on lui transmet les variables
        return $this->render('AppBundle:Default:blog.html.twig', array(
            // pour fournir des variables au template
            // a gauche, le nom qui sera utilisé dans le template
            // a droite, la valeur
            'lang' => $_locale,
            'title' => $title,
            'year' => $year
        ));
    }
    
    
    /**
     * @Route("/blog/", name="accueilBlog")
     */
    public function listArticleAction() {
        //appel d'une méthode de l'entité Article pour récupérer une liste d'articles sous cette forme :
        $tableArticles = $this->getDoctrine()->getManager()->getRepository(Article::class)->findAll() ;
        
        return $this->render('AppBundle:Default:blogaccueil.html.twig', array(
            'listeArticles' => $tableArticles
        ));
    }
    
    /**
     * @Route("/blog/{id}", name="articleBlog")
     */
    public function showArticleAction($id) {
         
        $articleDB = $this->getDoctrine()->getManager()->getRepository(Article::class)->findOneById($id);
        
        if(!$articleDB || $articleDB->getIsEnabled() != true) {
            $erreur = "Cet article n'existe pas ou n'est pas disponible actuellement" ;
            
            return $this->render('AppBundle:Default:article.html.twig', array(
                'erreur' => $erreur,
                'article' => ""
            ));
            /*throw $this->createNotFoundException(
                "Cet article n'existe pas ou n'est pas disponible actuellement"
            ) ;*/
        } else {
            return $this->render('AppBundle:Default:article.html.twig', array(
                'article' => $articleDB,
                'erreur' => ""
            ));
        }
    }
    
    
    /**
     * @Route("/newArticle", name="publierArticle")
     */

    public function createArticleAction(Request $request) {
        
        //création d'un nouvel article vide à hydrater
        $article = new Article() ;
        
        //valeur par défaut pour placeholder
        $article->setTitle('Le flux migratoire des canartichos au printemps');
        
        $monFormulaire = $this->createForm(ArticleType::class, $article) ;
        
        $monFormulaire->handleRequest($request) ;
                
        if ($monFormulaire->isValid()) {
            $em = $this->getDoctrine()->getManager() ;
            $em->persist($article) ;
            $em->flush() ;
            
            $request->getSession()->getFlashBag()->add('notice', 'Article enregistré') ;
            
        return $this->redirect($this->generateUrl('articleBlog', [
                'id' => $article->getId()
            ]
        ));
            
        }
        
        return $this->render('AppBundle:Default:postArticle.html.twig', array(
            'formulaire' => $monFormulaire->createView()
        ));
    }
    
    /**
     * @Route("/editArticle/{id}", name="editerArticle")
     */

    public function editArticleAction($id, Request $request) {
        
        $articleDB = $this->getDoctrine()->getManager()->getRepository(Article::class)->findOneById($id);
        
        if(!$articleDB) {
            $erreur = "Cet article n'existe pas, il n'est donc pas possible de le modifier" ;
            
            return $this->render('AppBundle:Default:editArticle.html.twig', array(
                'erreur' => $erreur,
                'formulaire' => ""
            ));
            
        } else {
        
        $monFormulaire = $this->createForm(ArticleType::class, $articleDB) ;
        
        $monFormulaire->handleRequest($request) ;
                
        if ($monFormulaire->isValid()) {
            $dateEdition = new \DateTime('now') ;
            $articleDB->setUpdatedAt($dateEdition) ;
            $em = $this->getDoctrine()->getManager() ;
            $em->persist($articleDB) ;
            $em->flush() ;
            
            $request->getSession()->getFlashBag()->add('notice', 'Article enregistré') ;
            
            return $this->redirect($this->generateUrl('articleBlog', [
                    'id' => $articleDB->getId()
                ]
            ));
        } 
          
            return $this->render('AppBundle:Default:editArticle.html.twig', array(
                'erreur' => "",
                'formulaire' => $monFormulaire->createView()
        ));
        }
        
    }
    
    
    
    /**
     * Crée un formulaire pour supprimer un Article
     */
    private function createDeleteForm(Article $article)
    {
        //on crée un formulaire
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('article_delete', array('id' => $article->getId())))
            ->setMethod('DELETE')
            ->add('delete', SubmitType::class)
            ->getForm()
        ;
    }
    
    /**
     * @Route("/deleteArticle/{id}", name="supprimerArticle")
     */

    public function deleteArticleAction($id, Request $request) {
        
        $articleToRIP = $this->getDoctrine()->getManager()->getRepository(Article::class)->findOneById($id);
        
        $formSuppression = createDeleteForm($articleToRIP)
    }
    
}