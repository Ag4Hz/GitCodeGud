<?php

namespace Tests\Support;

class FakeSocialiteUser
{
    public string $token;
    public ?string $refreshToken;

    public function __construct(
        private readonly string $id,
        private readonly ?string $email,
        private readonly ?string $name,
        private readonly ?string $nickname,
        private readonly ?string $avatar,
        string $token = 'token-1',
        ?string $refreshToken = 'refresh-1',
    ) {
        $this->token = $token;
        $this->refreshToken = $refreshToken;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }
}

