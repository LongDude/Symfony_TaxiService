<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Tariff;
use App\Entity\User;
use App\Entity\Driver;
use App\Core\QueryFilters;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function getFilteredList(
        array $filters = [], 
        ?int $userId = null, 
        ?int $driverId = null
    ): array {
        $qb = $this->createQueryBuilder('o')
            ->select([
                'u1.phone as phone',
                'o.from_loc as from_loc',
                'o.dest_loc as dest_loc',
                'o.distance as distance',
                'o.orderedAt as orderedAt',
                'u1.name as user_name',
                'u2.name as driver_name',
                't.name as tariff_name',
                'o.price as price'
            ])
            ->leftJoin('o.tariff', 't')
            ->leftJoin('o.user_id', 'u1')
            ->leftJoin('o.driver', 'd')
            ->leftJoin('d.user', 'u2');

        $qfb = new QueryFilters($qb, $filters);
        $qfb
        ->range('ordered_at')
        ->like('name', 'driver_name')
        ->exact('tariff', 'o.tariff_id');

        if ($userId !== null) {
            $qb->andWhere('o.user_id = :user_id')
               ->setParameter('user_id', $userId);
        }

        if ($driverId !== null) {
            $qb->andWhere('o.driver = :driver_id')
               ->setParameter('driver_id', $driverId);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function getAvailableRides(array $filters = [], $distance = 0): array
    {
        $distance = (float) $distance;
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select([
                'u.name as driver_name',
                'd.id as driver_id',
                'd.rating as rating',
                't.name as tariff_name',
                't.id as tariff_id',
                '(t.base_price + (CASE WHEN :distance > t.base_dist THEN (:distance - t.base_dist) ELSE 0 END) * t.dist_cost) as price'
            ])
            ->from(Driver::class, 'd')
            ->leftjoin('d.tariff', 't')
            ->leftjoin('d.user', 'u')
            ->setParameter('distance', $distance);

        $qfb = new QueryFilters($qb, $filters);
        $qfb
        ->exact('tariff_id', 't.id')
        ->range('rating', 'd.rating');
        
        return $qb->getQuery()->getArrayResult();
    }

    public function addOrder(
        string $from_loc,
        string $dest_loc,
        float $distance,
        float $price,
        int $driver_id,
        int $user_id,
        int $tariff_id,
        ?string $orderedAt=null,
    ): Order {
        $order = new Order();
        $order
        ->setFromLoc($from_loc)
        ->setDestLoc($dest_loc)
        ->setDistance($distance)
        ->setPrice($price)
        ->setDriver($this->getEntityManager()->getRepository(Driver::class)->find($driver_id))
        ->setUserId($this->getEntityManager()->getRepository(User::class)->find($user_id))
        ->setTariff($this->getEntityManager()->getRepository(Tariff::class)->find($tariff_id));
        if (empty($orderedAt)){
            $order->setOrderedAt(new \DateTimeImmutable($orderedAt));
        }

        $this->getEntityManager()->persist($order);
        $this->getEntityManager()->flush();
        return $order;
    }

    public function importCsv(string $filePath): bool
    {
        $em = $this->getEntityManager();
        if (($handle = fopen($filePath, 'r')) !== false) {
            // Skip header row if exists
            fgetcsv($handle);
            
            $em->getConnection()->beginTransaction();
            
            try {
                while (($data = fgetcsv($handle))) {
                    $order = new Order();
                    $order
                    ->setFromLoc($data[0])
                    ->setDestLoc($data[1])
                    ->setDistance($data[2])
                    ->setDriver($this->getEntityManager()->getRepository(Driver::class)->find($data[3]))
                    ->setUserId($this->getEntityManager()->getRepository(User::class)->find($data[4]));
                    if (!empty($data[5])){
                        $order
                        ->setOrderedAt(new \DateTimeImmutable($data[5]));
                    }
                    $em->persist($order);
                }
                
                $em->flush();
                $em->getConnection()->commit();
            } catch (\Exception $e) {
                $em->getConnection()->rollBack();
                throw $e;
            }
            
            fclose($handle);
        }
        return true;
    }
}
