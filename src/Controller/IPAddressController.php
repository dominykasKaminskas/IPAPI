<?php

namespace App\Controller;

use App\Entity\IPAddress;
use App\Repository\IPAddressRepository;
use App\Repository\BlacklistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

final class IPAddressController extends AbstractController
{
    // #[Route('/api/ip', name: 'api_ip', methods: ['GET'])]
    // #[OA\Get(
    //     path: "/api/ip",
    //     summary: "All IP addresses",
    //     responses: [
    //         new OA\Response(
    //             response: 200,
    //             description: "API is healthy",
    //             content: new OA\JsonContent(
    //                 properties: [
    //                     new OA\Property(property: "status", type: "string", example: "ok")
    //                 ]
    //             )
    //         )
    //     ]
    // )]
    // public function index(ValidatorInterface $validator, IPAddressRepository $ipAddressRepository): JsonResponse
    // {
    //     $ip_addresses = $ipAddressRepository->findAll();

    //     $errors = $validator->validate($ip_addresses);

    //     if (count($errors) > 0) {
    //         /*
    //          * Uses a __toString method on the $errors variable which is a
    //          * ConstraintViolationList object. This gives us a nice string
    //          * for debugging.
    //          */
    //         $errorsString = (string) $errors;

    //         return $this->json(['error' => $errorsString]);
    //     }

    //     return $this->json($ip_addresses);
    // }

    // #[Route('/api/create/{ip}', name: 'api_ip_create', methods: ['POST'])]
    // #[OA\Response(
    //     path: "/api/create/{ip}",
    //     summary: "Add new IP",
    //     responses: [
    //         new OA\Response(
    //             response: 200,
    //             description: "API is healthy",
    //             content: new OA\JsonContent(
    //                 properties: [
    //                     new OA\Property(property: "status", type: "string", example: "ok")
    //                 ]
    //             )
    //         )
    //     ]
    // )]
    // #[OA\Parameter(
    //     name: 'ip_address',
    //     in: 'query',
    //     description: 'The field used to order rewards',
    //     schema: new OA\Schema(type: 'string')
    // )]
    private function create(EntityManagerInterface $entityManager, string $ip, string $api_key)
    {
        $ip_address = new IPAddress();
        $ip_address->setAddress($ip);
        $this->collectData($ip_address, $ip, $api_key);

        $entityManager->persist($ip_address);
        $entityManager->flush();

        return $ip_address;
    }

    private function collectData(IPAddress $ip_address, string $ip, string $api_key)
    {
        $client = new \OK\Ipstack\Client($api_key);
        $location = $client->get($ip, false);

        $ip_address->setContinent($location->getContinentName());
        $ip_address->setCountry($location->getCountryName());
        $ip_address->setCity($location->getCity());
        $ip_address->setRegion($location->getRegionName());
        $ip_address->setZip($location->getZip());
    }

    #[Route('/api/retrieve/{ip}&{api_key}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/retrieve/{ip}&{api_key}",
        summary: "Retrieve IP Address",
        responses: [
            new OA\Response(
                response: 200,
                description: "IP Address Information",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "int", example: "0"),
                        new OA\Property(property: "address", type: "string", example: "134.201.250.155"),
                        new OA\Property(property: "createdAt", type: "datetime_immutable", example: "2025-11-16T00:00:00+00:00"),
                        new OA\Property(property: "updatedAt", type: "datetime_immutable", example: "2025-11-16T00:00:00+00:00"),
                        new OA\Property(property: "continent", type: "string", example: "North America"),
                        new OA\Property(property: "country", type: "string", example: "United States"),
                        new OA\Property(property: "city", type: "string", example: "Huntington Beach"),
                        new OA\Property(property: "region", type: "string", example: "California"),
                        new OA\Property(property: "zip", type: "string", example: "92647")
                    ]
                )
            )
        ]
    )]
    public function retrieve(EntityManagerInterface $entityManager, BlacklistRepository $blacklistRepository, string $ip, string $api_key): JsonResponse
    {
        $blacklist = $blacklistRepository->findOneBy(['address' => $ip]);
        if ($blacklist !== null ) {
            return $this->json(['error' => 'IP address ' . $ip . ' is blacklisted.']);
        }

        $repository = $entityManager->getRepository(IPAddress::class);
        $ip_address = $repository->findOneBy(['address' => $ip]);
        
        if (null !== $ip_address) { // if IP exists in the database

            $date = new \DateTimeImmutable()->sub(new \DateInterval('P1D'));

            if ($ip_address->getUpdatedAt() < $date) { // if updatedAt is more than 1 day

                $this->collectData($ip_address, $ip, $api_key); // fetch data
                $ip_address->setUpdatedAt(); // update timestamp
                $entityManager->persist($ip_address);
                $entityManager->flush(); // execute query
            }
        }

        else { // if IP doesn't exist in the database
            $ip_address = $this->create($entityManager, $ip, $api_key); // create object
        }
        
        return $this->json($ip_address); // return object
    }

    #[Route('/api/delete/{ip}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/delete/{ip}",
        summary: "Delete IP Address",
        responses: [
            new OA\Response(
                response: 200,
                description: "OK"
            ),
            new OA\Response(
                response: 400,
                description: "IP Address not found"
            )
        ]
    )]
    public function delete(EntityManagerInterface $entityManager, string $ip): Response
    {
        $repository = $entityManager->getRepository(IPAddress::class);
        $ip_address = $repository->findOneBy(['address' => $ip]);

        if ($ip_address === null) {
            return new Response('IP address ' . $ip . ' not found in the database.', 400);
        }
           
        $entityManager->remove($ip_address);
        $entityManager->flush();
        return new Response('IP address ' . $ip . ' deleted.', 200);
    }

}
