<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="auth_users")
 * @ORM\Entity(repositoryClass="UserRepository")
 */
class User implements AdvancedUserInterface, \Serializable
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=60, unique=true)
     * @ORM\Column(nullable=true)
     */
    private $email;

    /**
     * @var EmailAddress[]
     *
     * @ORM\OneToMany(targetEntity="EmailAddress", mappedBy="user", cascade={"ALL"})
     */
    private $emailAddresses;

    /**
     * @var EmailAddress
     */
    private $primaryEmailAddress;

    /**
     * @ORM\Column(name="roles", type="string")
     */
    private $role;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @var Group[]
     *
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="members", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="group_user")
     */
    private $groups;

    /**
     * @var App\Entity\OAuth\Client[]
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\OAuth\Client", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="user_oauthclient")
     */
    private $authorizedApplications;

    public function __construct()
    {
        $this->role = 'ROLE_USER';
        $this->isActive = true;
        $this->groups = new ArrayCollection();
        $this->authorizedApplications = new ArrayCollection();
        $this->emailAddresses = new ArrayCollection();
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getSalt()
    {
        return null;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getRoles()
    {
        return array($this->role);
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    public function eraseCredentials()
    {
    }

    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            $this->getPrimaryEmailAddress(),
        ));
    }

    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
            $this->primaryEmailAddress,
        ) = unserialize($serialized);
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return $this->getPrimaryEmailAddress()->isVerified()||$this->role === 'ROLE_SUPER_ADMIN';
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->isActive;
    }

    public function setEnabled($enabled)
    {
        $this->isActive = $enabled;
        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        if($password) {
            $this->password = $password;
        }

        return $this;
    }

    /**
     * Get email
     *
     * @deprecated
     * @return string
     */
    public function getEmail()
    {
        return $this->getPrimaryEmailAddress()->getEmail();
    }

    /**
     * Add groups
     *
     * @param \App\Entity\Group $groups
     * @return User
     */
    public function addGroup(\App\Entity\Group $groups)
    {
        $this->groups[] = $groups;

        return $this;
    }

    /**
     * Remove groups
     *
     * @param \App\Entity\Group $groups
     */
    public function removeGroup(\App\Entity\Group $groups)
    {
        $this->groups->removeElement($groups);
    }

    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    public function _getAllGroupNames()
    {
        $groups = array();
        foreach($this->groups as $group) {
            $groups = array_merge($groups, $group->_getAllGroupNames());
        }
        return $groups;
    }

    /**
     * Add authorizedApplications
     *
     * @param \App\Entity\OAuth\Client $authorizedApplications
     * @return User
     */
    public function addAuthorizedApplication(\App\Entity\OAuth\Client $authorizedApplications)
    {
        $this->authorizedApplications[] = $authorizedApplications;

        return $this;
    }

    /**
     * Remove authorizedApplications
     *
     * @param \App\Entity\OAuth\Client $authorizedApplications
     */
    public function removeAuthorizedApplication(\App\Entity\OAuth\Client $authorizedApplications)
    {
        $this->authorizedApplications->removeElement($authorizedApplications);
    }

    /**
     * Get authorizedApplications
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAuthorizedApplications()
    {
        return $this->authorizedApplications;
    }

    /**
     * Add emailAddresses
     *
     * @param \App\Entity\EmailAddress $emailAddresses
     * @return User
     */
    public function addEmailAddress(\App\Entity\EmailAddress $emailAddresses)
    {
        $this->emailAddresses[] = $emailAddresses;

        return $this;
    }

    /**
     * Remove emailAddresses
     *
     * @param \App\Entity\EmailAddress $emailAddresses
     */
    public function removeEmailAddress(\App\Entity\EmailAddress $emailAddresses)
    {
        $this->emailAddresses->removeElement($emailAddresses);
    }

    /**
     * Get emailAddresses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEmailAddresses()
    {
        if($this->email) {
            $email = new EmailAddress();
            $email->setEmail($this->email);
            $email->setUser($this);
            $email->setPrimary(true);
            $this->emailAddresses->add($email);
            $this->email = null;
        }
        return $this->emailAddresses;
    }

    /**
     * Get primaryEmailAddress
     *
     * @return \App\Entity\EmailAddress
     */
    public function getPrimaryEmailAddress()
    {
        if($this->primaryEmailAddress && !$this->getEmailAddresses()) {
            return $this->primaryEmailAddress;
        }
        foreach($this->getEmailAddresses()->toArray() as $email) {
            if($email->isPrimary())
                return $this->primaryEmailAddress = $email;
        }
        return $this->getEmailAddresses()->get(0)->setPrimary(true);
    }

}
