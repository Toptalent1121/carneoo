<?php
namespace App\Repository;
use Doctrine\ORM\EntityManager;

/**
 * Class GenericRepository
 * @package App\Repository
 */
abstract class GenericRepository {
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager() {
        return $this->entityManager;
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection() {
        return $this->getEntityManager()->getConnection();
    }
}