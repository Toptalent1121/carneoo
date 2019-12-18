<?php

namespace App\Repository;

use App\Entity\Page;
use App\Traits\Panel\RepositoryTrait;
use App\Traits\Panel\DatatableRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Twig_Environment;

/**
 * @method Page|null find($id, $lockMode = null, $lockVersion = null)
 * @method Page|null findOneBy(array $criteria, array $orderBy = null)
 * @method Page[]    findAll()
 * @method Page[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageRepository extends ServiceEntityRepository
{

    use RepositoryTrait,
        DatatableRepositoryTrait;

    public function __construct(RegistryInterface $registry, Twig_Environment $templating)
    {
        parent::__construct($registry, Page::class);
        $this->templating = $templating;
    }

    public function getTreeStructure(Page $node = null)
    {
        $em      = $this->getEntityManager();
        $qb      = $em->createQueryBuilder('p');
        $results = $qb->select('p')
            ->from('App\Entity\Page', 'p')
            ->where('p.parent IS NULL')
            ->orderBy('p.page_order', 'asc')
            ->getQuery()
            ->getResult();
        if (empty($results)) {
            return [];
        }

        return $this->buildNodes($results);
    }

    /**
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getHomePage()
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->where('p.active = 1');
        $queryBuilder->andWhere('p.home_page = 1');

        return $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $slug
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findBySlug(string $slug)
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->where('p.active = 1');
        $queryBuilder->andWhere('p.slug LIKE :slug');

        $queryBuilder->setParameter('slug', $slug);

        return $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return mixed
     */
    public function getPages()
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->where('p.active = 1');
        $queryBuilder->andWhere('p.menu = 1');
        $queryBuilder->andWhere('p.parent IS NULL');
        $queryBuilder->andWhere('p.home_page != 1');

        $queryBuilder->orderBy('p.page_order', 'ASC');

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    private function buildNodes($collection)
    {
        $array = [];
        foreach ($collection as $page) {
            $node = $this->createNodeData($page);
            if (count($page->getChildren()) > 0) {
                $node->children = $this->buildNodes($page->getChildren());
            }
            $array[] = $node;
        }
        return $array;
    }

    private function createNodeData(Page $page)
    {
        $updatedByEntity = $page->getUpdatedBy();
        $createdByEntity = $page->getCreatedBy();
        $createdBy = "";
        $updatedBy = "";

        if(!empty($createdByEntity)){
           $createdBy = $createdByEntity->getName().' '.$createdByEntity->getLastName();
        }

        if(!empty($updatedByEntity)){
           $updatedBy = $updatedByEntity->getName().' '.$updatedByEntity->getLastName();
        }


        $results            = new \stdClass();
        $results->label = $page->getName();
        $results->id = $page->getId();
        $results->data = new \stdClass();
        $results->data->name      = $page->getName();
        $results->data->createdBy = $createdBy;
        $results->data->createdAt = $page->getCreatedAt();
        $results->data->updatedBy = $updatedBy;
        $results->data->updatedAt = $page->getUpdatedAt();
        $results->data->order     = $page->getPageOrder();
        $results->data->id        = $page->getId();
        $results->data->actions = $this->renderActions($page);
        $results->data->info = $this->renderInfo($page);

        return $results;
    }

    private function renderActions(Page $page)
    {
        return $this->templating->render('panel/page/_partials/actions.html.twig',
                [
                    'entity' => $page,
        ]);
    }

    private function renderInfo(Page $page)
    {
        return $this->templating->render('panel/page/_partials/extraInfo.html.twig',
                [
                    'entity' => $page,
        ]);
    }
}