<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'invalid_message' => 'Informe um e-mail válido.',

                'attr' => [
                    'class' => 'auth-input',
                    'placeholder' => 'seu@email.com',
                    'autocomplete' => 'username',
                    'id' => 'loginEmail',
                    'aria-describedby' => 'loginEmailErrors',
                    'data-error-required' => 'Informe o e-mail.',
                    'data-error-email' => 'Informe um e-mail válido.',
                ],

                'constraints' => [
                    new NotBlank(
                        message: 'Informe o e-mail.'
                    ),

                    new Email(
                        message: 'Informe um e-mail válido.'
                    ),
                ],
            ])
            
            ->add('password', PasswordType::class, [
                'label' => 'Senha',

                'attr' => [
                    'class' => 'auth-input has-toggle',
                    'placeholder' => 'Informe sua senha',
                    'autocomplete' => 'current-password',
                    'id' => 'loginPassword',
                    'minlength' => 6,
                    'aria-describedby' => 'loginPasswordErrors',
                    'data-error-required' => 'Informe a senha.',
                    'data-error-minlength' => 'A senha deve ter pelo menos 6 caracteres.',
                ],

                'constraints' => [
                    new NotBlank(
                        message: 'Informe a senha.'
                    ),
                    new Length(
                        min: 6,
                        minMessage: 'A senha deve ter pelo menos {{ limit }} caracteres.'
                    ),
                ],
            ])
        ;
    }
}
