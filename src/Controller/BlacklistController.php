<?php

namespace App\Controller;

use App\Entity\Blacklist;
use App\Entity\IPAddress;
use App\Repository\IPAddressRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;

final class BlacklistController extends AbstractController
{
    #[Route('/api/blacklist/{ip}', methods: ['POST'])]
    #[OA\Post(
        path: "/api/blacklist/{ip}",
        summary: "Add IP Address to the blacklist",
        responses: [
            new OA\Response(
                response: 200,
                description: "OK"
            )
        ]
    )]
    public function blacklist(EntityManagerInterface $entityManager, string $ip): Response
    {
        $blacklist = new Blacklist();

        $repository = $entityManager->getRepository(IPAddress::class);
        $ip_address = $repository->findOneBy(['address' => $ip]);

        $blacklist->setAddress($ip);

        if ($ip_address !== null) {
            $blacklist->setIpAddress($ip_address);
        }

        $entityManager->persist($blacklist);
        $entityManager->flush();

        return new Response('IP address ' . $ip . ' added to the blacklist', 200);
    }

    #[Route('/api/unblacklist/{ip}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/unblacklist/{ip}",
        summary: "Remove IP Address from blacklist",
        responses: [
            new OA\Response(
                response: 200,
                description: "OK"
            ),
            new OA\Response(
                response: 400,
                description: "IP Address not found in the blacklist"
            )
        ]
    )]
    public function unBlacklist(EntityManagerInterface $entityManager, string $ip)
    {
        $repository = $entityManager->getRepository(Blacklist::class);
        $blacklist = $repository->findOneBy(['address' => $ip]);

        if ($blacklist === null) {
            return new Response('IP address ' . $ip . ' not found in the blacklist.', 400);
        }

        $entityManager->remove($blacklist);
        $entityManager->flush();
        return new Response('IP address ' . $ip . ' removed from the blacklist.', 200);
    }
}
