<?php

namespace Catalog\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Catalog
 *
 * @ORM\Table(name="catalog", indexes={@ORM\Index(name="id_parent", columns={"idParent"}), @ORM\Index(name="idStatus", columns={"idStatus"})})
 * @ORM\Entity
 */
class Catalog
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="translit", type="string", length=60, nullable=true)
     */
    private $translit;

    /**
     * @var \Catalog\Entity\Catalog
     *
     * @ORM\ManyToOne(targetEntity="Catalog\Entity\Catalog")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idParent", referencedColumnName="id")
     * })
     */
    private $idParent;

    /**
     * @var \Data\Entity\Status
     *
     * @ORM\ManyToOne(targetEntity="Data\Entity\Status")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idStatus", referencedColumnName="id")
     * })
     */
    private $idStatus;

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
     * Set name
     *
     * @param string $name
     * @return Catalog
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set translit
     *
     * @param string $translit
     * @return Catalog
     */
    public function setTranslit($translit)
    {
        $this->translit = $translit;

        return $this;
    }

    /**
     * Get translit
     *
     * @return string
     */
    public function getTranslit()
    {
        return $this->translit;
    }

    /**
     * Set idParent
     *
     * @param \Catalog\Entity\Catalog $idParent
     * @return Catalog
     */
    public function setIdParent(\Catalog\Entity\Catalog $idParent = null)
    {
        $this->idParent = $idParent;

        return $this;
    }

    /**
     * Get idParent
     *
     * @return \Catalog\Entity\Catalog 
     */
    public function getIdParent()
    {
        return $this->idParent;
    }

    /**
     * Set idStatus
     *
     * @param \Data\Entity\Status $idStatus
     * @return Catalog
     */
    public function setIdStatus(\Data\Entity\Status $idStatus = null)
    {
        $this->idStatus = $idStatus;

        return $this;
    }

    /**
     * Get idStatus
     *
     * @return \Data\Entity\Status
     */
    public function getIdStatus()
    {
        return $this->idStatus;
    }
	
	/**
     * @return array
     */
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    /**
     * Populate from an array.
     *
     * @param array $data
     */
    public function populate($data = array())
    {
        $this->id       = $data['id'];
        $this->name     = $data['name'];
        $this->translit = $data['translit'];
        $this->idParent = $data['idParent'];
		$this->idStatus = $data['idStatus'];
    }

}
