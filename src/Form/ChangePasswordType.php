<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @extends AbstractType<mixed>
 */
class ChangePasswordType extends AbstractType
{
    private const INPUT_CLASS = 'w-full px-4 py-2 border border-gray-800 rounded-lg outline-none';
    private const LABEL_CLASS = 'block text-sm font-bold text-gray-900 mb-2';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'mapped' => false,
                'attr' => ['class' => self::INPUT_CLASS],
                'label_attr' => ['class' => self::LABEL_CLASS],
                'constraints' => [
                    // Vérifie  que ce mot de passe correspond bien à celui de l'utilisateur connecté
                    new UserPassword([
                        'message' => 'Le mot de passe actuel est invalide.',
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                // On ne lie pas ce champ à l'entité pour éviter de stocker le mot de passe en clair avant le hachage
                'mapped' => false,
                'invalid_message' => 'Les deux mots de passe doivent être identiques.',
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'attr' => ['class' => self::INPUT_CLASS],
                    'label_attr' => ['class' => self::LABEL_CLASS],
                ],
                'second_options' => [
                    'label' => 'Confirmer le nouveau mot de passe',
                    'attr' => ['class' => 'mt-4 '.self::INPUT_CLASS],
                    'label_attr' => ['class' => 'block text-sm font-bold text-gray-900 mb-2 mt-4'],
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 12,
                        'minMessage' => 'Votre mot de passe doit faire au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                    // Obligation d'avoir un mot de passe robuste
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
                        'message' => 'Votre mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial (@$!%*?&).',
                    ]),
                ],
            ])
        ;
    }
}
