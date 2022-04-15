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

        $result = [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'createdDate' => $user->getCreatedDate()->format(\DateTime::ISO8601),
            'websiteId' => $user->getWebsite()->getId(),
            'parentId' => $user->getParent()?->getId()
        ];

        return new JsonResponse($result, 201, ["Content-Type" => "application/json;charset=UTF-8"]);
    }
}
