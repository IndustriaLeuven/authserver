<?php
/* Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2015  Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Security\User;

use App\Entity\EmailAddress;
use App\Entity\User;
use KULeuven\ShibbolethBundle\Security\ShibbolethUserProviderInterface;
use KULeuven\ShibbolethBundle\Security\ShibbolethUserToken;
use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Doctrine\Common\Persistence\ManagerRegistry;

class UserProvider extends EntityUserProvider implements ShibbolethUserProviderInterface
{
    private $shibAutoEnable;
    private $em;
    public function __construct(ManagerRegistry $registry, $shibAutoEnable)
    {
        parent::__construct($registry, 'AppBundle:User');
        $this->em = $registry->getManagerForClass('AppBundle:User');
        $this->shibAutoEnable = $shibAutoEnable;
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
        $user = new User();

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
            $emailAddress = new EmailAddress();
            $emailAddress->setEmail($token->getMail());
            $emailAddress->setVerified(true);
            $emailAddress->setPrimary(true);
            $emailAddress->setUser($user);
            $user->addEmailAddress($emailAddress);
            $this->em->persist($emailAddress);
        }
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
