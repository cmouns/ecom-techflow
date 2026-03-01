<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

/**
 * @extends AbstractType<Product>
 */
class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputClasses = 'w-full px-4 py-2.5 mt-1 mb-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-black focus:border-transparent outline-none transition-all shadow-sm placeholder:text-gray-400';

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du produit',
                'attr' => [
                    'class' => $inputClasses,
                    'placeholder' => 'Ex: Écran 4K ULTRA HD',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du produit',
                'required' => false,
                'attr' => [
                    'class' => $inputClasses,
                    'rows' => 5,
                    'placeholder' => 'Décrivez les caractéristiques techniques...',
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix de vente ',
                'currency' => 'EUR',
                'divisor' => 100,
                'attr' => [
                    'class' => $inputClasses,
                    'placeholder' => 'Ex: 190.99',
                ],
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Quantité en stock',
                'attr' => [
                    'class' => $inputClasses,
                    'placeholder' => 'Ex: 10',
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie du produit',
                'placeholder' => 'Choisir une catégorie',
                'attr' => ['class' => $inputClasses],
            ])
            ->add('images', FileType::class, [
                'label' => 'Galerie photos (Plusieurs fichiers possibles)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'constraints' => [
                    new All([
                        new File([
                            'maxSize' => '2M',
                            'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                            'mimeTypesMessage' => 'Format invalide (JPG, PNG ou WEBP)',
                        ]),
                    ]),
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 mt-1 rounded-lg border border-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-700 file:text-white hover:file:bg-blue-800 cursor-pointer',
                    'accept' => 'image/*',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
