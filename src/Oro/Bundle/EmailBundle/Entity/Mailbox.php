<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

/**
 * @ORM\Table(name="oro_email_mailbox")
 * @ORM\Entity(repositoryClass="Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository")
 * @UniqueEntity(fields={"email", "label"})
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-envelope"
 *          },
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          }
 *      }
 * )
 */
class Mailbox implements EmailOwnerInterface, EmailHolderInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", unique=true)
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255)
     */
    protected $label;

    /**
     * @var MailboxProcessSettings
     *
     * @ORM\OneToOne(targetEntity="MailboxProcessSettings",
     *     cascade={"all"}, orphanRemoval=true
     * )
     * @ORM\JoinColumn(name="process_settings_id", referencedColumnName="id", nullable=true)
     */
    protected $processSettings;

    /**
     * @var EmailOrigin
     *
     * @ORM\OneToOne(
     *     targetEntity="Oro\Bundle\EmailBundle\Entity\EmailOrigin",
     *     cascade={"all"}, orphanRemoval=true, inversedBy="mailbox"
     * )
     * @ORM\JoinColumn(name="origin_id", referencedColumnName="id", nullable=true)
     */
    protected $origin;

    /**
     * @var EmailUser[]
     *
     * @ORM\OneToMany(targetEntity="Oro\Bundle\EmailBundle\Entity\EmailUser", mappedBy="mailboxOwner")
     */
    protected $emailUsers;

    /**
     * @var OrganizationInterface
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * Collection of users authorized to view mailbox emails.
     *
     * @var Collection<User>
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\UserBundle\Entity\User",
     *      cascade={"persist", "remove"}
     * )
     * @ORM\JoinTable(name="oro_email_mailbox_users",
     *     joinColumns={@ORM\JoinColumn(name="mailbox_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     * )
     */
    protected $authorizedUsers;

    /**
     * Collection of roles authorised to view mailbox emails.
     *
     * @var Collection<Role>
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\UserBundle\Entity\Role",
     *      cascade={"persist", "remove"}
     * )
     * @ORM\JoinTable(name="oro_email_mailbox_roles",
     *     joinColumns={@ORM\JoinColumn(name="mailbox_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $authorizedRoles;

    public function __construct()
    {
        $this->authorizedUsers = new ArrayCollection();
        $this->authorizedRoles = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return MailboxProcessSettings
     */
    public function getProcessSettings()
    {
        return $this->processSettings;
    }

    /**
     * @param MailboxProcessSettings $processSettings
     *
     * @return $this
     */
    public function setProcessSettings($processSettings)
    {
        $this->processSettings = $processSettings;

        return $this;
    }

    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return __CLASS__;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailFields()
    {
        return ['email'];
    }

    /**
     * @return EmailOrigin
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * @param EmailOrigin $origin
     *
     * @return $this
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * @return OrganizationInterface
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param OrganizationInterface $organization
     *
     * @return $this
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get first name
     *
     * @return string
     */
    public function getFirstName()
    {
         return $this->getLabel();
    }

    /**
     * Get last name
     *
     * @return string
     */
    public function getLastName()
    {
        return 'Mailbox';
    }

    /**
     * @return EmailUser[]
     */
    public function getEmailUsers()
    {
        return $this->emailUsers;
    }

    /**
     * @param EmailUser[] $emailUsers
     *
     * @return $this
     */
    public function setEmailUsers($emailUsers)
    {
        $this->emailUsers = $emailUsers;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getAuthorizedRoles()
    {
        return $this->authorizedRoles->toArray();
    }

    /**
     * @param array|Collection $authorizedRoles
     *
     * @return $this
     */
    public function setAuthorizedRoles($authorizedRoles)
    {
        if (!($authorizedRoles instanceof Collection) && is_array($authorizedRoles)) {
            $authorizedRoles = new ArrayCollection($authorizedRoles);
        }
        $this->authorizedRoles = $authorizedRoles;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getAuthorizedUsers()
    {
        return $this->authorizedUsers->toArray();
    }

    /**
     * @param array|Collection $authorizedUsers
     *
     * @return $this
     */
    public function setAuthorizedUsers($authorizedUsers)
    {
        if (!($authorizedUsers instanceof Collection) && is_array($authorizedUsers)) {
            $authorizedUsers = new ArrayCollection($authorizedUsers);
        }
        $this->authorizedUsers = $authorizedUsers;

        return $this;
    }
}
