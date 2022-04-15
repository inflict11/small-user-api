<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Website;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private function getUserArray(User $user): array
    {
        return [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'createdDate' => $user->getCreatedDate()->format(\DateTime::ISO8601),
            'updatedDate' => $user->getUpdatedDate()?->format(\DateTime::ISO8601),
            'websiteId' => $user->getWebsite()->getId(),
            'parentId' => $user->getParent()?->getId(),
        ];
    }

    #[Route('/user', name: 'create_user', methods: ['POST'])]
    public function createUser(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $apiKey = $request->headers->get('Authorization');
        if (!$apiKey) {
            return new JsonResponse('Authorization header is not found', 401);
        }

        $website = $entityManager->getRepository(Website::class)->findOneBy(['apiKey' => $apiKey]);
        if (!$website) {
            return new JsonResponse('Invalid authorization key', 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse('Invalid json with parameters', 415);
        }

        $requiredParams = ['firstName', 'lastName', 'email'];

        foreach ($requiredParams as $param) {
            if (empty($data[$param])) {
                return new JsonResponse("$param field is required", 400);
            }
        }
        if ($entityManager->getRepository(User::class)->findOneBy([
            'email' => $data['email'],
            'website' => $website
        ])) {
            return new JsonResponse("Duplicate email for this website", 400);
        }
        // TODO validate email, validate length

        $user = new User();
        if (!empty($data['parentId'])) {
            $parentUser = $entityManager->find(User::class, (int) $data['parentId']);
            if (!$parentUser) {
                return new JsonResponse("Invalid parentId: User " . $data['parentId']
                    . " is not found", 400);
            }
            if ($parentUser->getParent()) {
                return new JsonResponse("Invalid parentId: User " . $data['parentId']
                    . " is child itself", 400);
            }
            $user->setParent($parentUser);
        }
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setEmail($data['email']);
        $user->setWebsite($website);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse($this->getUserArray($user), 201, ["Content-Type" => "application/json;charset=UTF-8"]);
    }

    #[Route('/user/{id}', name: 'get_user', methods: ['GET'])]
    public function getUserEntity(int $id, ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $apiKey = $request->headers->get('Authorization');
        if (!$apiKey) {
            return new JsonResponse('Authorization header is not found', 401);
        }

        $website = $entityManager->getRepository(Website::class)->findOneBy(['apiKey' => $apiKey]);
        if (!$website) {
            return new JsonResponse('Invalid authorization key', 403);
        }
        $user = $entityManager->find(User::class, $id);
        if (!$user || !$user->getIsActive()) {
            return new JsonResponse('User not found', 404);
        }

        return new JsonResponse($this->getUserArray($user), 200, ["Content-Type" => "application/json;charset=UTF-8"]);
    }

    #[Route('/user/{id}', name: 'update_user', methods: ['PUT'])]
    public function updateUser(int $id, ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $apiKey = $request->headers->get('Authorization');
        if (!$apiKey) {
            return new JsonResponse('Authorization header is not found', 401);
        }

        $website = $entityManager->getRepository(Website::class)->findOneBy(['apiKey' => $apiKey]);
        if (!$website) {
            return new JsonResponse('Invalid authorization key', 403);
        }
        $user = $entityManager->find(User::class, $id);
        if (!$user) {
            return new JsonResponse('User not found', 404);
        }
        $data = json_decode($request->getContent(), true);
        if (!$user || !$user->getIsActive()) {
            return new JsonResponse('Invalid json with parameters', 415);
        }
        if (!empty($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (!empty($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        $user->setUpdatedDate(new \DateTime());
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse($this->getUserArray($user), 200, ["Content-Type" => "application/json;charset=UTF-8"]);
    }


    #[Route('/user/{id}', name: 'delete_user', methods: ['DELETE'])]
    public function deleteUser(int $id, ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $apiKey = $request->headers->get('Authorization');
        if (!$apiKey) {
            return new JsonResponse('Authorization header is not found', 401);
        }

        $website = $entityManager->getRepository(Website::class)->findOneBy(['apiKey' => $apiKey]);
        if (!$website) {
            return new JsonResponse('Invalid authorization key', 403);
        }
        $user = $entityManager->find(User::class, $id);
        if (!$user || !$user->getIsActive()) {
            return new JsonResponse('User not found', 404);
        }
        $user->setIsActive(false);
        $user->setDeletedDate(new \DateTime());
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse('', 204, ["Content-Type" => "application/json;charset=UTF-8"]);
    }
}
