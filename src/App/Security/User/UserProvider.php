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
    private $shibAutoEnable;
    public function __construct(ManagerRegistry $registry, UserRepository $repo, $shibAutoEnable)
    {
        $this->repo = $repo;
        $this->shibAutoEnable = $shibAutoEnable;
        parent::__construct($registry, 'AppBundle:User', 'username');
    }

    public function loadUserByUsername($username)
    {
        @list($user, $domain) = explode('@', $username, 2);
        if($domain === 'kuleuven.be') {
            return parent::loadUserByUsername($user);
        } else {
            return parent::loadUserByUsername($username);
        }
    }

    function createUser(ShibbolethUserToken $token)
    {
        $user = $this->repo->newInstance();

        $user->setDisplayName($token->getFullName());
        $username = $token->getUsername();
        @list($localname, $domain) = explode('@', $username, 2);
        if($domain === 'kuleuven.be') {
            $user->setUsername($localname)
                ->setEnabled($this->shibAutoEnable);
        } else {
            $user->setUsername($username)
                ->setEnabled(false);
        }
        if(!$token->isStudent('kuleuven.be')) {
            $user->setEnabled(false);
        }
        $user->setPasswordEnabled(0);

        if($token->hasAttribute('mail')) {
            $user->getPrimaryEmailAddress()
                ->setEmail($token->getMail())
                ->setVerified(true);
        } else {
            $user->removeEmailAddress($user->getPrimaryEmailAddress());
        }
        $this->repo->create($user);

        return $user;
    }
}
