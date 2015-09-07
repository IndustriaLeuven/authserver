<?php

namespace App\Entity;

use App\Entity\OAuth\UserAuthorization;
use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Util\Random;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="auth_users")
 * @ORM\Entity(repositoryClass="UserRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable
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
     * @ORM\Column(type="guid")
     */
    private $guid;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     * @Gedmo\Versioned
     */
    private $username;

    /**
     * @ORM\Column(name="display_name", type="string", length=255)
     * @Gedmo\Versioned
     */
    private $displayName;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Gedmo\Versioned
     */
    private $password;

    /**
     * @ORM\Column(name="password_enabled", type="integer")
     * @Gedmo\Versioned
     */
    private $passwordEnabled;

    /**
     * @ORM\Column(name="password_reset_token", type="string", nullable=true)
     * @Gedmo\Versioned
     */
    private $passwordResetToken;

    /**
     * @var EmailAddress[]
     *
     * @ORM\OneToMany(targetEntity="EmailAddress", mappedBy="user", cascade={"ALL"}, orphanRemoval=true)
     */
    private $emailAddresses;

    /**
     * @var EmailAddress
     */
    private $primaryEmailAddress;

    /**
     * @ORM\Column(name="roles", type="string")
     * @Gedmo\Versioned
     */
    private $role;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     * @Gedmo\Versioned
     */
    private $enabled;

    /**
     * @var Group[]
     *
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="members", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="group_user")
     */
    private $groups;

    /**
     * @var UserAuthorization[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\OAuth\UserAuthorization", mappedBy="user", fetch="EXTRA_LAZY")
     */
    private $authorizedApplications;

    /**
     * @var UserProperty[]
     *
     * @ORM\OneToMany(targetEntity="UserProperty", mappedBy="user", cascade={"ALL"})
     */
    private $userProperties;

    /**
     * Temporary storage for user properties persist hack
     * @see __rescueUserProperties__()
     * @internal
     */
    private $__userProperties__;

    public function __construct()
    {
        $this->role = 'ROLE_USER';
        $this->isActive = true;
        $this->groups = new ArrayCollection();
        $this->authorizedApplications = new ArrayCollection();
        $this->emailAddresses = new ArrayCollection();
        $this->userProperties = new ArrayCollection();
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
        return $this->passwordEnabled == 1?$this->password:'!';
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
        if($this->role === 'ROLE_SUPER_ADMIN')
            return true;
        if(!$this->getPrimaryEmailAddress())
            return true;
        return $this->getPrimaryEmailAddress()->isVerified();
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return strtoupper($this->guid);
    }

    public function getMigrateId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param  string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     *
     * @param  string $displayName
     * @return User
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Set password
     *
     * @param  string $password
     * @return User
     */
    public function setPassword($password)
    {
        if ($password) {
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
        $primary = $this->getPrimaryEmailAddress();
        if($primary)
            return $primary->getEmail();
        return null;
    }

    /**
     * Add groups
     *
     * @param  \App\Entity\Group $groups
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

    public function getGroupsRecursive()
    {
        $groups = array();
        foreach ($this->groups as $group) {
            $group->getGroupsRecursive($groups);
        }
        return array_values($groups);
    }

    /**
     * Add authorizedApplications
     *
     * @param  UserAuthorization $authorizedApplications
     * @return User
     */
    public function addAuthorizedApplication(UserAuthorization $authorizedApplications)
    {
        $this->authorizedApplications[] = $authorizedApplications;

        return $this;
    }

    /**
     * Remove authorizedApplications
     *
     * @param UserAuthorization $authorizedApplications
     */
    public function removeAuthorizedApplication(UserAuthorization $authorizedApplications)
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
     * @param  \App\Entity\EmailAddress $emailAddresses
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
        return $this->emailAddresses;
    }

    /**
     * Get primaryEmailAddress
     *
     * @return \App\Entity\EmailAddress|null
     */
    public function getPrimaryEmailAddress()
    {
        if ($this->primaryEmailAddress && !$this->getEmailAddresses()) {
            return $this->primaryEmailAddress;
        } elseif(!$this->getEmailAddresses()) {
            return null;
        }

        foreach ($this->getEmailAddresses() as $email) {
            if($email->isPrimary())
                return $this->primaryEmailAddress = $email;
        }

        if($mail = $this->getEmailAddresses()->get(0))
            $mail->setPrimary(true);
        return $mail;
    }

    public function getGuid()
    {
        return strtoupper($this->guid);
    }

    public function setGuid($guid)
    {
        $this->guid = $guid;

        return $this;
    }

    public function getPasswordEnabled()
    {
        return $this->passwordEnabled;
    }

    public function setPasswordEnabled($passwordEnabled)
    {
        $this->passwordEnabled = $passwordEnabled;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPasswordResetToken()
    {
        return $this->passwordResetToken;
    }

    public function generatePasswordResetToken()
    {
        $this->passwordResetToken = Random::generateToken();

        return $this;
    }

    public function clearPasswordResetToken()
    {
        $this->passwordResetToken = null;

        return $this;
    }

    public function getUserProperties()
    {
        return $this->userProperties;
    }

    public function getUserPropertiesMap()
    {
        $map = array();
        foreach ($this->userProperties as $property) {
            $map[$property->getProperty()->getName()] = $property->getData();
        }

        return $map;
    }

    /**
     * Hack to get the user properties out of the persist loop when this entity
     * itself is persisted.
     * This is required becaue the user properies have a composite primary key,
     * which consists of this entity id (unknown until a flush happens) and the property entity id.
     * Doctrine needs to know the id of this entity before the user properties entities can be
     * persisted, but it also requires all entities in the mapped collection to be persisted
     * before, or together with the main entity that is persisted.
     * The result of this is an unsatisfiable condition, since only persisted entities can
     * be flushed to the database. That is why the user properties collection gets copied
     * to a temporary variable before persisting, en gets restored after persisting.
     *
     * @internal
     * @ORM\PrePersist
     */
    public function __rescueUserProperties__()
    {
        $this->__userProperties__ = $this->userProperties;
        $this->userProperties = null;
    }

    /**
     * @see __rescueUserProperties__()
     * @internal
     * @ORM\PostPersist
     */
    public function __restoreUserProperties__()
    {
        $this->userProperties = $this->__userProperties__;
    }
}
