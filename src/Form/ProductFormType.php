<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProductFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Product name cannot be empty']),
                    new Assert\Length(['min' => 3, 'max' => 255, 'minMessage' => 'Product name must be at least 3 characters']),
                ]
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Price',
                'currency' => 'TND',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Price cannot be empty']),
                    new Assert\Positive(['message' => 'Price must be greater than 0']),
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Description cannot be empty']),
                    new Assert\Length(['min' => 5, 'max' => 255]),
                ]
            ])
            ->add('category', TextType::class, [
                'label' => 'Category',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Category cannot be empty']),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Save Product',
                'attr' => ['class' => 'btn btn-primary']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
