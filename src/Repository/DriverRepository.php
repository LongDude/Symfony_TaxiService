<?php

namespace App\Repository;

use App\Entity\Driver;
use App\Entity\User;
use App\Entity\Tariff;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use App\Core\QueryFilters;
/**
 * @extends ServiceEntityRepository<Driver>
 */
class DriverRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Driver::class);
    }

    public function listDriversDetails(): array
    { 
        return $this->createQueryBuilder('d')
            ->select([
                'u.name as name',
                'u.phone',
                'u.email',
                'd.intership',
                'd.car_license',
                'd.car_brand',
                't.name as tariff_name'
            ])
            ->join('d.user_id', 'u')
            ->leftJoin('d.tariff_id', 't')
            ->getQuery()
            ->getResult();
    }

    public function getFilteredList(array $filters = []): array
    {   
        $qb = $this->createQueryBuilder('d')
            ->select([
                'u.name as name',
                'u.phone',
                'u.email',
                'd.intership',
                'd.car_license',
                'd.car_brand',
                't.name as tariff_name'
            ])
            ->join('d.user_id', 'u')
            ->leftJoin('d.tariff_id', 't');
        $qfb = new QueryFilters($qb, $filters);

        $qfb->like('name', 'u.name')
            ->like('phone', 'u.phone')
            ->like('email', 'u.email')
            ->exact('car_license', 'd.car_license')
            ->exact('tariff_id', 't.id');

        return $qb->getQuery()->getResult();
    }

    public function addDriver(
        User $user,
        int $intership,
        string $carLicense,
        string $carBrand,
        ?Tariff $tariff = null,
        float $rating = 0.0
    ): Driver {
        $driver = new Driver();
        $driver->setUser($user)
               ->setIntership($intership)
               ->setCarLicense($carLicense)
               ->setCarBrand($carBrand)
               ->setTariff($tariff)
               ->setRating($rating);

        $this->getEntityManager()->persist($driver);
        $this->getEntityManager()->flush();

        return $driver;
    }

    public function updateDriver(
        Driver $driver,
        ?int $intership = null,
        ?string $carLicense = null,
        ?string $carBrand = null,
        ?Tariff $tariff = null,
        ?float $rating = null
    ): Driver {
        if ($intership !== null) {
            $driver->setIntership($intership);
        }
        if ($carLicense !== null) {
            $driver->setCarLicense($carLicense);
        }
        if ($carBrand !== null) {
            $driver->setCarBrand($carBrand);
        }
        if ($tariff !== null) {
            $driver->setTariff($tariff);
        }
        if ($rating !== null) {
            $driver->setRating($rating);
        }

        $this->getEntityManager()->flush();
        return $driver;
    }

    public function importCsv(string $filePath): bool
    {
        $em = $this->getEntityManager();
        $userRep = $em->getRepository(User::class);
        $tariffRep = $em->getRepository(Tariff::class);

        if (($handle = fopen($filePath, 'r')) !== false) {
            // Skip header row if exists
            fgetcsv($handle);
            
            $em->getConnection()->beginTransaction();
            
            try {
                while (($data = fgetcsv($handle))) {
                    // Assuming CSV format: name,phone,email,intership,car_license,car_brand,tariff_id

                    $user = $userRep->findOneBy(['phone' => $data[1]]) ??
                            $userRep->findOneBy(['email' => $data[2]]) ??
                            new User();

                    $user->setName($data[0])
                         ->setPhone($data[1])
                         ->setEmail($data[2]);

                    $tariff = null;
                    if (!empty($data[6])){
                        $tariff = $tariffRep->find($data[6]);
                    }

                    // Если пользователь уже существовал, у него может быть регистрация водителя
                    $driver = $user->getDriver() ?? new Driver();
                    $driver->setUser($user) // В теории повторно присвоить тот же элемент можно
                           ->setIntership((int)$data[3])
                           ->setCarLicense($data[4])
                           ->setCarBrand($data[5])
                           ->setTariff($tariff);
                    
                    $em->persist($user);
                    $em->persist($driver);
                }
                
                $em->flush();
                $em->getConnection()->commit();
            } catch (Exception $e) {
                $em->getConnection()->rollBack();
                throw $e;
            }
            
            fclose($handle);
        }
        return true;
    }
}
