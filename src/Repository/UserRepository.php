<?php

namespace App\Repository;

use App\Entity\User;
use App\Core\QueryFilters;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
        ->select('u.name', 'u.phone', 'u.email', 'u.roles');

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

    public function importCsv(array $csvData, UserPasswordHasherInterface $hasher): int
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

                $user_name = $row[0];
                $user_phone = $row[1];
                $user_email = $row[2];
                $user_password = $row[3];

                $user = $this->findOneBy(['email' => $user_email]);
                if (!$user){
                    $user = new User();
                    $em->persist($user);
                }
                $user
                ->setName($user_name)
                ->setPhone($user_phone)
                ->setEmail($user_email)
                ->setPassword($hasher->hashPassword($user, $user_password));

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

}
