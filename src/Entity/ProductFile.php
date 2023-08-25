<?php

namespace Pixel\Module\ProductFiles\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class ProductFile
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="id_product", type="integer", nullable=false)
     */
    private $idProduct;

    /**
     * @var int
     *
     * @ORM\Column(name="id_shop", type="integer", nullable=false)
     */
    private $idShop;

    /**
     * @var int
     *
     * @ORM\Column(name="id_lang", type="integer", nullable=false)
     */
    private $idLang;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="text", nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="file", type="text", nullable=true)
     */
    private $file;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer", nullable=false)
     */
    private $position;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     *
     * @return ProductFile
     */
    public function setId(?int $id): ProductFile
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdProduct(): int
    {
        return $this->idProduct;
    }

    /**
     * @param int $idProduct
     *
     * @return ProductFile
     */
    public function setIdProduct(int $idProduct): ProductFile
    {
        $this->idProduct = $idProduct;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getIdShop(): ?int
    {
        return $this->idShop;
    }

    /**
     * @param int|null $idShop
     *
     * @return ProductFile
     */
    public function setIdShop(?int $idShop): ProductFile
    {
        $this->idShop = $idShop;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getIdLang(): ?int
    {
        return $this->idLang;
    }

    /**
     * @param int|null $idLang
     *
     * @return ProductFile
     */
    public function setIdLang(?int $idLang): ProductFile
    {
        $this->idLang = $idLang;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     *
     * @return ProductFile
     */
    public function setTitle(?string $title): ProductFile
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return ProductFile
     */
    public function setDescription(?string $description): ProductFile
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->file;
    }

    /**
     * @param string|null $file
     *
     * @return ProductFile
     */
    public function setFile(?string $file): ProductFile
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int|null $position
     *
     * @return ProductFile
     */
    public function setPosition(?int $position): ProductFile
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'id_product' => $this->getIdProduct(),
            'id_shop' => $this->getIdShop(),
            'id_lang' => $this->getIdLang(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'file' => $this->getFile(),
            'position' => $this->getPosition(),
        ];
    }
}
