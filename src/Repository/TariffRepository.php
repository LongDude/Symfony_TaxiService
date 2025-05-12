<?php

namespace App\Repository;

use App\Entity\Tariff;
use App\Core\QueryFilters;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @extends ServiceEntityRepository<Tariff>
 */
class TariffRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tariff::class);
    }

    public function getFilteredList(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.name', 't.base_price', 't.base_dist', 't.dist_cost');
        $qfb = new QueryFilters($qb, $filters);
        $qfb->like('name', 't.name')
            ->range('base_price', 't.base_price');
        return $qb->getQuery()->getResult();
    }

    public function addTariff(
        string $name,
        float $base_price,
        float $base_dist,
        float $dist_cost,
    ): Tariff {
        $tariff = new Tariff();
        $tariff
            ->setName($name)
            ->setBasePrice($base_price)
            ->setBaseDist($base_dist)
            ->setDistCost($dist_cost);

        $this->getEntityManager()->persist($tariff);
        $this->getEntityManager()->flush();
        return $tariff;
    }

    public function updateTariff(
        Tariff $tariff,
        ?string $name = null,
        ?float $base_price = null,
        ?float $base_dist = null,
        ?float $dist_cost = null,
    ): Tariff {
        if ($name !== null) {
            $tariff->setName($name);
        }
        if ($base_price !== null) {
            $tariff->setBasePrice($base_price);
        }
        if ($base_dist !== null) {
            $tariff->setBaseDist($base_dist);
        }
        if ($dist_cost !== null) {
            $tariff->setDistCost($dist_cost);
        }
        $this->getEntityManager()->flush();
        return $tariff;
    }

    public function importCsv(array $csvData): int
    {
        $em = $this->getEntityManager();
        $connection = $em->getConnection();
        $connection->beginTransaction();

        $isHeader = true;
        $count = 0;

        try {
            foreach ($csvData as $row) {
                if ($isHeader) {
                    $isHeader = false;
                    continue;
                }

                if (count($row) < 4){
                    throw new RuntimeException('Invalid CSV. Expected 4 columns');
                }

                $tariff = $this->findOneBy(['name' => $row[0]]);
                if (!$tariff){
                    $tariff = new Tariff();
                    $em->persist($tariff);
                }

                $tariff
                    ->setName($row[0])
                    ->setBasePrice((float)$row[1])
                    ->setBaseDist((float)$row[2])
                    ->setDistCost((float)$row[3]);

                $count++;
            }

            $em->flush();
            $connection->commit();
            return $count;
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
