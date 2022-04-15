<?php

namespace App\Tests\Controller;

use App\Controller\UserController;
use App\Entity\User;
use App\Entity\Website;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

class GetUserTest extends TestCase
{
    public function testGetUserWithoutAuthHeader(): void
    {
        $objectManager = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManager::class);

        $objectManager->expects($this->never())
            ->method('getManager')
            ->willReturn($em);

        $request = $this->createMock(Request::class);
        $request->headers = new HeaderBag();

        $userController = new UserController();
        $result = $userController->getUserEntity(1, $objectManager, $request);

        $this->assertStringContainsString('401 Unauthorized', $result);
        $this->assertStringContainsString('Authorization header is not found', $result);
    }

    public function testGetUserWithInvalidApiKey(): void
    {
        $badKey = 'random';
        $objectManager = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManager::class);
        $repository = $this->createMock(UserRepository::class);

        $objectManager->expects($this->once())
            ->method('getManager')
            ->willReturn($em);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['apiKey' => $badKey])
            ->willReturn(null);

        $request = $this->createMock(Request::class);
        $request->headers = new HeaderBag(['Authorization' => $badKey]);

        $userController = new UserController();
        $result = $userController->getUserEntity(1, $objectManager, $request);

        $this->assertStringContainsString('403 Forbidden', $result);
        $this->assertStringContainsString('Invalid authorization key', $result);
    }

    public function testGetNonExistentUser(): void
    {
        $key = 'random';
        $objectManager = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManager::class);
        $repository = $this->createMock(UserRepository::class);

        $objectManager->expects($this->once())
            ->method('getManager')
            ->willReturn($em);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['apiKey' => $key])
            ->willReturn(new Website());
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $request = $this->createMock(Request::class);
        $request->headers = new HeaderBag(['Authorization' => $key]);

        $userController = new UserController();
        $result = $userController->getUserEntity(1, $objectManager, $request);

        $this->assertStringContainsString('404 Not Found', $result);
        $this->assertStringContainsString('User not found', $result);
    }

    public function testGetNonActiveUser(): void
    {
        $key = 'random';
        $objectManager = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManager::class);
        $repository = $this->createMock(UserRepository::class);

        $user = new User();
        $user->setIsActive(false);

        $objectManager->expects($this->once())
            ->method('getManager')
            ->willReturn($em);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['apiKey' => $key])
            ->willReturn(new Website());
        $em->expects($this->once())
            ->method('find')
            ->willReturn($user);

        $request = $this->createMock(Request::class);
        $request->headers = new HeaderBag(['Authorization' => $key]);

        $userController = new UserController();
        $result = $userController->getUserEntity(1, $objectManager, $request);

        $this->assertStringContainsString('404 Not Found', $result);
        $this->assertStringContainsString('User not found', $result);
    }

    public function testGetActiveUser(): void
    {
        $key = 'random';
        $firstName = 'first test name';
        $lastName = 'last test name';
        $email = 'email@test.ru';
        $objectManager = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManager::class);
        $repository = $this->createMock(UserRepository::class);

        $user = $this->createPartialMock(User::class, ['getId']);
        $website = $this->createPartialMock(Website::class, ['getId']);

        $user->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $website->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $user->setIsActive(true);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setEmail($email);
        $user->setCreatedDate(new \DateTime());
        $user->setWebsite($website);


        $objectManager->expects($this->once())
            ->method('getManager')
            ->willReturn($em);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['apiKey' => $key])
            ->willReturn($website);
        $em->expects($this->once())
            ->method('find')
            ->willReturn($user);

        $request = $this->createMock(Request::class);
        $request->headers = new HeaderBag(['Authorization' => $key]);

        $userController = new UserController();
        $result = $userController->getUserEntity(1, $objectManager, $request);

        $this->assertStringContainsString('200 OK', $result);
        $this->assertStringContainsString('"firstName":"' . $firstName . '"', $result);
        $this->assertStringContainsString('"lastName":"' . $lastName . '"', $result);
        $this->assertStringContainsString('"email":"' . $email . '"', $result);
    }
}
