<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Rbs\SecurityBundle\Entity\User;
use Sylius\Component\Resource\Model\ResourceInterface;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Project
 *
 * @ORM\Table(name="project")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProjectRepository")
 */
class Project implements ResourceInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var  User
     *
     * @JMS\Exclude
     *
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="Rbs\SecurityBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="entry_by_user_id", referencedColumnName="id")
     * })
     */
    private $entryBy;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="entry_time", type="datetime")
     */
    private $entryTime;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Project
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Project
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return User
     */
    public function getEntryBy()
    {
        return $this->entryBy;
    }

    /**
     * @param User $entryBy
     */
    public function setEntryBy($entryBy)
    {
        $this->entryBy = $entryBy;
    }

    /**
     * @return \DateTime
     */
    public function getEntryTime()
    {
        return $this->entryTime;
    }

    /**
     * @param \DateTime $entryTime
     */
    public function setEntryTime($entryTime)
    {
        $this->entryTime = $entryTime;
    }
}

