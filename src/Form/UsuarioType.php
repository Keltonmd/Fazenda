<?php

namespace App\Form;

use App\Dto\UsuarioDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UsuarioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['include_nome']) {
            $builder->add('nome', TextType::class, [
                'label' => 'Nome Completo',
                'invalid_message' => 'Informe um nome válido.',

                'attr' => [
                    'class' => 'auth-input',
                    'placeholder' => 'Digite seu nome completo',
                    'autocomplete' => 'name',
                ],

                'constraints' => [
                    new NotBlank(
                        message: 'Informe o nome completo.'
                    ),

                    new Length(
                        min: 2,
                        minMessage: 'O nome completo deve conter pelo menos {{ limit }} caracteres.'
                    ),
                ],
            ]);
        }

        if ($options['include_email']) {
            $builder->add('email', EmailType::class, [
                'label' => 'E-mail',
                'invalid_message' => 'Informe um e-mail válido.',

                'attr' => [
                    'class' => 'auth-input',
                    'placeholder' => 'Digite seu e-mail',
                    'autocomplete' => 'email',
                ],

                'constraints' => [
                    new NotBlank(
                        message: 'Informe o e-mail.'
                    ),

                    new Email(
                        message: 'Informe um e-mail válido.'
                    ),
                ],
            ]);
        }

        if ($options['include_password']) {
            $constraints = [
                new Length(
                    min: 8,
                    minMessage: 'A senha deve conter pelo menos {{ limit }} caracteres.'
                ),
            ];

            if ($options['password_required']) {
                array_unshift($constraints, new NotBlank(
                    message: 'Informe a senha.'
                ));
            }

            if ($options['password_require_complexity']) {
                $constraints[] = new Regex(
                    pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
                    message: 'A senha deve conter letra maiúscula, minúscula, número e símbolo.'
                );
            }

            $builder->add('password', PasswordType::class, [
                'label' => 'Senha',
                'invalid_message' => 'Informe uma senha válida.',

                'attr' => [
                    'class' => 'auth-input has-toggle',
                    'placeholder' => 'Mínimo 8 caracteres',
                    'autocomplete' => 'new-password',
                ],

                'constraints' => $constraints,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UsuarioDTO::class,
            'include_nome' => true,
            'include_email' => true,
            'include_password' => true,
            'password_required' => true,
            'password_require_complexity' => true,
        ]);
    }
}
