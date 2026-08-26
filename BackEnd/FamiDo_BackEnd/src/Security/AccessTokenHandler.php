<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        return new UserBadge($accessToken, function (string $token): UserInterface {
            $user = $this->userRepository->findOneBy(['apiToken' => $token]);

            if (!$user) {
                throw new UserNotFoundException();
            }

            return $user;
        });
    }
}
