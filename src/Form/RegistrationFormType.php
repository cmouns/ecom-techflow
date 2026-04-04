<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @extends AbstractType<User>
 */
class RegistrationFormType extends AbstractType
{
    private const LABEL_ATTR_CLASS = 'block text-sm font-medium text-gray-300 mb-2';
    private const ATTR_CLASS = 'w-full px-4 py-2.5 bg-white border border-gray-600 text-black rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-colors';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'label_attr' => ['class' => self::LABEL_ATTR_CLASS],
                'attr' => [
                    'placeholder' => 'Prénom',
                    'class' => self::ATTR_CLASS,
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'label_attr' => ['class' => self::LABEL_ATTR_CLASS],
                'attr' => [
                    'placeholder' => 'Nom',
                    'class' => self::ATTR_CLASS,
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'label_attr' => ['class' => self::LABEL_ATTR_CLASS],
                'attr' => [
                    'placeholder' => 'nom@exemple.fr',
                    'class' => self::ATTR_CLASS,
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // mapped => false empêche d'injecter le mot de passe en clair dans l'entité User
                'mapped' => false,
                'label' => 'Mot de passe',
                'label_attr' => ['class' => self::LABEL_ATTR_CLASS],
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => '••••••••',
                    'class' => 'w-full px-4 py-2.5 bg-white border border-gray-600 text-black rounded-lg focus:ring-2 focus:white outline-none transition-colors',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un mot de passe']),
                    new Length([
                        'min' => 12,
                        'minMessage' => 'Votre mot de passe doit faire au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                    // Stratégie de sécurité stricte pour l'inscription
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
                        'message' => 'Votre mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial (@$!%*?&).',
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => false,
                'attr' => [
                    'class' => 'w-4 h-4 text-blue-600 bg-white border-gray-600 rounded focus:ring-blue-500 focus:ring-2 cursor-pointer',
                ],
                'constraints' => [
                    new IsTrue(['message' => 'Vous devez accepter nos conditions d\'utilisation.']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'registration_item',
        ]);
    }
}
