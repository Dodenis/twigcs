<?php

namespace FriendsOfTwig\Twigcs\Rule;

use FriendsOfTwig\Twigcs\Lexer;
use FriendsOfTwig\Twigcs\TwigPort\Token;
use FriendsOfTwig\Twigcs\TwigPort\TokenStream;

class NamedVariable extends AbstractRule implements RuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function check(TokenStream $tokens)
    {
        $violations = [];

        $canBeParams = [];
        while (!$tokens->isEOF()) {
            $token = $tokens->getCurrent();

            if (Token::PUNCTUATION_TYPE === $token->getType() &&
                '(' === $token->getValue() &&
                Token::NAME_TYPE === $tokens->look(Lexer::PREVIOUS_TOKEN)->getType()
            ) {
                $canBeParams[] = $token->getLine().$token->getColumn();
            }

            if (Token::OPERATOR_TYPE === $token->getType() &&
                '=' === $token->getValue()
            ) {
                if (count($canBeParams) > 0) {
                    if (Token::WHITESPACE_TYPE === $tokens->look(Lexer::PREVIOUS_TOKEN)->getType() ||
                        Token::WHITESPACE_TYPE === $tokens->look(Lexer::NEXT_TOKEN)->getType()
                    ) {
                        if (Token::WHITESPACE_TYPE === $tokens->look(Lexer::PREVIOUS_TOKEN)->getType()) {
                            $namedArgument = $tokens->look(-2)->getValue();
                        } else {
                            $namedArgument = $tokens->look(Lexer::PREVIOUS_TOKEN)->getValue();
                        }
                        $violations[] = $this->createViolation(
                            $tokens->getSourceContext()->getPath(),
                            $token->getLine(),
                            $token->getColumn(),
                            sprintf(
                                'The "=" operator of a named argument "%s" must not be surrounded by spaces.',
                                $namedArgument
                            )
                        );
                    }
                } else {
                    if (' ' !== $tokens->look(Lexer::PREVIOUS_TOKEN)->getValue()) {
                        $violations[] = $this->createViolation(
                            $tokens->getSourceContext()->getPath(),
                            $token->getLine(),
                            $token->getColumn(),
                            sprintf('There should be 1 space(s) between the "=" operator and its left operand.')
                        );
                    }
                    if (' ' !== $tokens->look(Lexer::NEXT_TOKEN)->getValue()) {
                        $violations[] = $this->createViolation(
                            $tokens->getSourceContext()->getPath(),
                            $token->getLine(),
                            $token->getColumn(),
                            sprintf('There should be 1 space(s) between the "=" operator and its right operand.')
                        );
                    }
                }
            }

            if (Token::PUNCTUATION_TYPE === $token->getType() &&
                ')' === $token->getValue()
            ) {
                array_pop($canBeParams);
            }

            $tokens->next();
        }

//        print_r($violations);
//        die();

        return $violations;
    }
}
