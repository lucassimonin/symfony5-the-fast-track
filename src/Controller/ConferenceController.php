<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ConferenceController extends AbstractController
{
    /**
     * Description $twig field
     *
     * @var Environment $twig
     */
    protected $twig;
    /**
     * Description $entityManager field
     *
     * @var EntityManager $entityManager
     */
    protected $entityManager;

    /**
     * ConferenceController constructor
     *
     * @param Environment            $twig
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        Environment $twig,
        EntityManagerInterface $entityManager
    ) {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", name="homepage")
     * @param Environment          $twig
     * @param ConferenceRepository $conferenceRepository
     *
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return new Response($this->twig->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll()
        ]));
    }

    /**
     * @Route("/conference/{slug}", name="conference")
     *
     * @param Request           $request
     * @param Conference        $conference
     * @param CommentRepository $commentRepository
     *
     * @param string            $photoDir
     *
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function show(Request $request, Conference $conference, CommentRepository $commentRepository, string $photoDir): Response
    {
        $offset = max(0, $request->query->getInt('offset'));
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setConference($conference);
            if ($photo = $form['photo']->getData()) {
                $filename = sprintf('%s.%s', bin2hex(random_bytes(6)), $photo->guessExtension());
                try {
                    $photo->move($photoDir, $filename);
                } catch (FileException $e) {

                }
                $comment->setPhotoFilename($filename);
            }
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            return $this->redirectToRoute('conference', [
                'slug' => $conference->getSlug()
            ]);
        }

        return new Response($this->twig->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
            'comment_form' => $form->createView()
        ]));
    }
}
