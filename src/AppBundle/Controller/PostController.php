<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Post;
use Doctrine\Common\Util\Debug;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Post controller.
 *
 * @Route("post")
 */
class PostController extends Controller
{
    /**
     * Lists all post entities.
     *
     * @Route("/", name="post_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $posts = $em->getRepository('AppBundle:Post')->findAll();

        return $this->render('post/index.html.twig', array(
            'posts' => $posts,
        ));
    }

    /**
     * Lists all post entities.
     *
     * @Route("/form", name="post_form")
     * @Method("GET")
     */
    public function formAction()
    {
        $post = new Post();
        $form = $this->createForm('AppBundle\Form\PostType', $post);

        $fields = ['fields' => []];

        foreach ($form->all() as $field) {
            $fieldOptions = [];
            /** @var FormInterface $field */
            $options = $field->getConfig()->getOptions();

            if(!empty($options['constraints'])) {
                foreach ($options['constraints'] as $constraint) {
                    $fieldOptions['constraints'][get_class($constraint)] = get_class($constraint);
                }
            }

            foreach (['required', 'max_length', 'pattern'] as $type) {
                if ($options[$type]) {
                    $fieldOptions['constraints'][$type] = $options[$type];
                }
            }

            $name = $field->getName();
            $type = $field->getConfig()->getType()->getBlockPrefix();
            echo $type. PHP_EOL;
            switch ($type) {
                case 'entity':
                    /** @var EntityManager $rep */
                    $className = $field->getConfig()->getOptions()['class'];
                    $fieldOptions['entity'] = $className;
                    $rep = $this->getDoctrine()->getRepository($className);
                    $dataCollector = $field->getConfig()->getAttributes()['data_collector/passed_options'];
                    if(isset($dataCollector['choices'])) {
                        $fieldOptions['choices'] = $dataCollector['choices'];
                    } else {

                        if (isset($dataCollector['query_builder']) && !empty($dataCollector['query_builder'])) {
                            $queryBuilder = call_user_func($dataCollector['query_builder'], $rep);
                        } else {
                            $queryBuilder = $rep->createQueryBuilder();
                        }

                        $data = $queryBuilder->getQuery()->getResult();
                    }

                    $fieldOptions['type'] = 'choice';
                    break;
                case 'choice':
                    $dataCollector = $field->getConfig()->getAttributes()['data_collector/passed_options'];
                    $fieldOptions['choices'] = $dataCollector['choices'];
                    break;
                default:
                    echo "ok";
                    break;
            }
            if($type == 'entity' && $field->getConfig()->getOptions()['class'] == 'AppBundle\Entity\Project') {
                $dataCollector = $field->getConfig()->getAttributes()['data_collector/passed_options'];
              //  var_dump($field->getConfig()->getOptions()); exit;
                $closer = $field->getConfig()->getAttributes()['data_collector/passed_options']['query_builder'];
                $rep = $this->getDoctrine()->getRepository($field->getConfig()->getOptions()['class'] );
                $queryBuilder = $closer($rep);
                Debug::dump($queryBuilder->getQuery()->getSql()); exit;
                var_dump($field->getConfig()->getAttributes()['data_collector/passed_options']['query_builder']); exit;
            }

            $fields['fields'][$name] = $fieldOptions;
        }

        return new JsonResponse($fields);
    }

    /**
     * Creates a new post entity.
     *
     * @Route("/new", name="post_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $post = new Post();
        $form = $this->createForm('AppBundle\Form\PostType', $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($post);
            $em->flush($post);

            return $this->redirectToRoute('post_show', array('id' => $post->getId()));
        }

        return $this->render('post/new.html.twig', array(
            'post' => $post,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a post entity.
     *
     * @Route("/{id}", name="post_show")
     * @Method("GET")
     */
    public function showAction(Post $post)
    {
        $deleteForm = $this->createDeleteForm($post);

        return $this->render('post/show.html.twig', array(
            'post' => $post,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing post entity.
     *
     * @Route("/{id}/edit", name="post_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Post $post)
    {
        $deleteForm = $this->createDeleteForm($post);
        $editForm = $this->createForm('AppBundle\Form\PostType', $post);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('post_edit', array('id' => $post->getId()));
        }

        return $this->render('post/edit.html.twig', array(
            'post' => $post,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a post entity.
     *
     * @Route("/{id}", name="post_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Post $post)
    {
        $form = $this->createDeleteForm($post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($post);
            $em->flush($post);
        }

        return $this->redirectToRoute('post_index');
    }

    /**
     * Creates a form to delete a post entity.
     *
     * @param Post $post The post entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Post $post)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('post_delete', array('id' => $post->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
