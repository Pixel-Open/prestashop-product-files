<?php

namespace Pixel\Module\ProductFiles\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class ProductFileLang
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
     * @ORM\Column(name="id_file", type="integer")
     */
    private $idFile;

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
     * @var int
     *
     * @ORM\Column(name="position", type="integer", nullable=false)
     */
    private $position;

    /**
     * @return int
     */
    public function getIdFile(): int
    {
        return $this->idFile;
    }

    /**
     * @param int|null $idFile
     *
     * @return ProductFile
     */
    public function setIdFile(?int $idFile): ProductFileLang
    {
        $this->idFile = $idFile;

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
    public function setIdLang(?int $idLang): ProductFileLang
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
    public function setTitle(?string $title): ProductFileLang
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
    public function setDescription(?string $description): ProductFileLang
    {
        $this->description = $description;

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
     * @return ProductFileLang
     */
    public function setPosition(?int $position): ProductFileLang
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
            'id_file' => $this->getIdFile(),
            'id_lang' => $this->getIdLang(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'position' => $this->getPosition(),
        ];
    }
}
