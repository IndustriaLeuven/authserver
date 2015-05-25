<?php

namespace App\Security\User;

use App\Entity\EmailAddress;
use App\Entity\User;
use App\Entity\UserRepository;
use KULeuven\ShibbolethBundle\Security\ShibbolethUserProviderInterface;
use KULeuven\ShibbolethBundle\Security\ShibbolethUserToken;
use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Doctrine\Common\Persistence\ManagerRegistry;

class UserProvider extends EntityUserProvider implements ShibbolethUserProviderInterface
{
    private $repo;
    public function __construct(ManagerRegistry $registry, UserRepository $repo)
    {
        $this->repo = $repo;
        parent::__construct($registry, 'AppBundle:User', 'username');
    }

    function createUser(ShibbolethUserToken $token)
    {
        $user = $this->repo->newInstance();

        $user->setDisplayName($token->getFullName());
        $user->setUsername($token->getUsername());
        $user->setPasswordEnabled(0);
        $user->isEnabled(true);

        $mail = new EmailAddress();
        $mail->setEmail($token->getMail());
        $mail->setVerified(true);
        $mail->setPrimary(true);
        $user->addEmailAddress($mail);

        $this->repo->create($user);

        return $user;
    }
}
