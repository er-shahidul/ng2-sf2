<?php

namespace AppBundle\Form;

use AppBundle\Repository\ProjectRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Required;

class PostType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, array(
            'constraints' => array(new Required())
        ))
            ->add('body')->add('status')->add('gender', ChoiceType::class, array(
                'expanded' => false,
                'choices' => array(
                    'm' => 'Male',
                    'f' => 'Female'
                )
            ))
            ->add('project', EntityType::class, array(
                'choice_label' => 'title',
                'class' => 'AppBundle\Entity\Project',
                'query_builder' => function(ProjectRepository $em) {
                    return $em->createQueryBuilder('p')->orderBy('p.title', 'asc');
                }
            ))        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Post'
        ));
    }

    public function getJson() {

    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_post';
    }


}
