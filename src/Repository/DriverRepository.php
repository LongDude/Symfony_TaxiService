<?php

namespace App\Repository;

use App\Entity\Driver;
use App\Entity\User;
use App\Entity\Tariff;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use App\Core\QueryFilters;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
            ->join('d.user', 'u')
            ->leftJoin('d.tariff', 't');
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

    public function importCsv(array $csvData, UserPasswordHasherInterface $hasher): int
    {
        $em = $this->getEntityManager();
        $userRep = $em->getRepository(User::class);
        $tariffRep = $em->getRepository(Tariff::class);
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

                if (count($row) < 8){
                    throw new RuntimeException('Invalid CSV. Expected 8 columns');
                }

                $user_name = $row[0];
                $user_phone = $row[1];
                $user_email = $row[2];
                $user_password = $row[3];
                $driver_intership = $row[4];
                $driver_car_license = $row[5];
                $driver_car_brand = $row[6];
                $tariff_name = $row[7];

                $user = $userRep->findOneBy(['email' => $user_email]);
                if (!$user){
                    $user = new User();
                    $user->addRole('ROLE_DRIVER');
                    $em->persist($user);
                }
                $user
                ->setName($user_name)
                ->setPhone($user_phone)
                ->setEmail($user_email)
                ->setPassword($hasher->hashPassword($user, $user_password));

                $tariff = $tariffRep->findOneBy(['name' => $tariff_name]);
                $driver = $user->getDriver();
                if (!$driver){
                    $driver = new Driver();
                    $driver->setUser($user);
                    $em->persist($driver);
                }
                $driver
                ->setIntership($driver_intership)
                ->setCarLicense($driver_car_license)
                ->setCarBrand($driver_car_brand)
                ->setTariff($tariff);

                $count++;
            }

            $em->flush();
            $connection->commit();
            return $count;
        } catch (Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
