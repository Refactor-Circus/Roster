<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Mcp\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

trait DescribesSsoConnection
{
    /**
     * A connection's protocol settings and switches, as creating and
     * updating one both take them.
     *
     * @return array<string, Type>
     */
    protected function ssoConnectionSettingsSchema(JsonSchema $schema): array
    {
        return [
            'issuer' => $schema->string()->description('OIDC: the issuer URL (https), e.g. https://example.okta.com.'),
            'tenant' => $schema->string()->description('Microsoft Entra ID: the tenant ID or verified domain. common/organizations/consumers are refused.'),
            'client_id' => $schema->string()->description('OIDC / Entra ID: the application (client) id.'),
            'client_secret' => $schema->string()->description('OIDC / Entra ID: the client secret. Never returned; omit on update to keep it.'),
            'metadata_url' => $schema->string()->description('SAML: the identity provider metadata URL (preferred).'),
            'entity_id' => $schema->string()->description('SAML without metadata: the IdP entity ID.'),
            'sso_url' => $schema->string()->description('SAML without metadata: the IdP single sign-on URL.'),
            'certificate' => $schema->string()->description('SAML without metadata: the IdP x509 signing certificate.'),
            'jit' => $schema->boolean()->description('Create accounts on first sign-in for the organization\'s domains. Defaults to true.'),
            'enforced' => $schema->boolean()->description('Require SSO for the organization\'s domains. Defaults to false.'),
            'enabled' => $schema->boolean()->description('Defaults to true.'),
        ];
    }
}
