<?php

namespace App\Entity;

use App\Enum\HttpMethod;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: App\Repository\ApiLogRepository::class)]
class ApiLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', enumType: HttpMethod::class, length: 10)]
    private HttpMethod $method;

    #[ORM\Column(type: 'integer')]
    private int $response;

    #[ORM\Column(length: 255)]
    private string $path;

    #[ORM\Column(type: 'integer', name: 'response_time')]
    private int $responseTime;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;


    public function getId(): ?int { return $this->id; }

    public function getMethod(): HttpMethod { return $this->method; }
    public function setMethod(HttpMethod $method): self { $this->method = $method; return $this; }


    public function getResponse(): int { return $this->response; }
    public function setResponse(int $response): self { $this->response = $response; return $this; }

    public function getPath(): string { return $this->path; }
    public function setPath(string $path): self { $this->path = $path; return $this; }

    public function getResponseTime(): int { return $this->responseTime; }
    public function setResponseTime(int $responseTime): self { $this->responseTime = $responseTime; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
