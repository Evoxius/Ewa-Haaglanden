<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\FileParam;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

use Symfony\Component\Filesystem\Filesystem;




/**
 * @Rest\Route("/api")
 */
final class PostController extends AbstractController
{
    private EntityManagerInterface $em;

    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->serializer = $serializer;
    }

    /**
     * @throws BadRequestHttpException
     *
     * @Rest\Post("/admin/dashboard/post/create", name="createPost", methods={"GET","POST"})
     */
    public function createAction(Request $request): JsonResponse
    {

        $title = $request->request->get('title');
        if (empty($title)) {
            throw new BadRequestHttpException('title cannot be empty');
        }

        $content = $request->request->get('content');
        if (empty($content)) {
            throw new BadRequestHttpException('content cannot be empty');
        }

        $img = $request->request->get('img');
        if (empty($img)) {
            throw new BadRequestHttpException('img cannot be empty');
        }


        $post = new Post();
        $post->setTitle($title);
        $post->setContent($content);
        
        

        define('UPLOAD_DIR', 'images/news/');
        $post->setImg($img);
        $img = $post->getImg($img);
        $img = str_replace('data:image/jpeg;base64,', '', $img);
        $img = str_replace('data:image/png;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        $file = uniqid() . '.jpeg';
        $read = UPLOAD_DIR . $file;
        $success = file_put_contents($read, $data);
        $post->setImg($file);


        $this->em->persist($post);
        $this->em->flush();
        $data = $this->serializer->serialize($post, JsonEncoder::FORMAT);

        return new JsonResponse($data, Response::HTTP_CREATED, [], true);
    }

    /**
     * @Rest\Get("/posts", name="findAllPosts")
     */
    public function findAllAction(): JsonResponse
    {
        $posts = $this->em->getRepository(Post::class)->findBy([], ['id' => 'DESC']);
        $data = $this->serializer->serialize($posts, JsonEncoder::FORMAT);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    /**
     * @Rest\Get("/admin/dashboard", name="findAllPosts2")
     */
    public function findAllAction2(): JsonResponse
    {
        $posts = $this->em->getRepository(Post::class)->findBy([], ['id' => 'DESC']);
        $data = $this->serializer->serialize($posts, JsonEncoder::FORMAT);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    /**
    * @Rest\Post("/admin/dashboard/post/delete/{id}", name="deletePost", methods={"DELETE"})
     */
    public function delete(Request $request, $id): JsonResponse
    {

        $em = $this->getDoctrine()->getManager();
        $post = $em->getRepository(Post::class)->find($id);
        
        $filename = $post->getImg();

        $filesystem = new Filesystem();
        $filesystem->remove('images/news/'.$filename);
        

        $em->remove($post);
        $em->flush();

        $data = $this->serializer->serialize($post, JsonEncoder::FORMAT);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }


    /**
     * @throws BadRequestHttpException
     *
     * @REST\RequestParam(name="title", description="news title", nullable=true)
     * @REST\RequestParam(name="content", description="news content", nullable=true)
     * @REST\RequestParam(name="img", description="news photo", nullable=true)
     * @Rest\Post("/admin/dashboard/post/edit/{id}", name="editPost", methods={"GET","PATCH"})
     */
    public function edit(ParamFetcher $paramFetcher, $id): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $post = $em->getRepository(Post::class)->find($id);

        $title = $paramFetcher->get('title');
        $content = $paramFetcher->get('content');
        $img = $paramFetcher->get('img');

        if (trim($title) !== '') {
            if ($post) {
                $post->setTitle($title); 
            }
        }

        if (trim($content) !== '') {
            if ($post) {
                $post->setContent($content); 
            }
        }

        if (trim($img) !== '') {
            if ($post) {
                define('UPLOAD_DIR', 'images/news/');
                $img = str_replace('data:image/jpeg;base64,', '', $img);
                $img = str_replace('data:image/png;base64,', '', $img);
                $img = str_replace(' ', '+', $img);
                $data = base64_decode($img);
                $file = uniqid() . '.jpeg';
                $read = UPLOAD_DIR . $file;
                $success = file_put_contents($read, $data);
                $post->setImg($file);
            }
        }

        $this->em->persist($post);
        $this->em->flush();

        $data = $this->serializer->serialize($post, JsonEncoder::FORMAT);
        
        return new JsonResponse($data, Response::HTTP_CREATED, [], true);
    
    }
}
