<?php

namespace App\Repository;

use App\Entity\User;
use App\Core\QueryFilters;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getFilteredList(array $filters = []): array {
        $qb = $this->createQueryBuilder('u')
        ->select('u.name', 'u.phone', 'u.email', 'u.role');

        $qfb = new QueryFilters($qb, $filters);
        $qfb->like('name', 'u.name')
            ->like('phone', 'u.phone')
            ->like('email', 'u.email');
        return $qb->getQuery()->getResult();
    }

    public function addUser(
        string $name,
        string $phone,
        string $email,
        string $password,
        string $role = 'client'
    ): User {
        $user = new User();
        $user->setName($name)
             ->setPhone($phone)
             ->setEmail($email)
             ->setPassword($password)
             ->addRole($role);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        return $user;
    }

    public function updateUser(
        User $user,
        ?string $name = null,
        ?string $phone = null,
        ?string $email = null,
        ?string $password = null,
        ?string $role = null
    ): User {
        if ($name !== null) {
            $user->setName($name);
        }
        if ($phone !== null) {
            $user->setPhone($phone);
        }
        if ($email !== null) {
            $user->setEmail($email);
        }
        if ($password !== null) {
            $user->setPassword($password);
        }
        if ($role !== null) {
            $user->addRole($role);
        }
        $this->getEntityManager()->flush();
        return $user;
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
                    // Assuming CSV format: name,phone,email,intership,car_license,car_brand,tariff_id

                    $user = $this->findOneBy(['phone' => $data[1]]) ??
                            $this->findOneBy(['email' => $data[2]]) ??
                            new User();

                    $user->setName($data[0])
                         ->setPhone($data[1])
                         ->setEmail($data[2])
                         ->setPassword($data[3])
                         ->addRole($data[4]);
                    
                    $em->persist($user);
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

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
