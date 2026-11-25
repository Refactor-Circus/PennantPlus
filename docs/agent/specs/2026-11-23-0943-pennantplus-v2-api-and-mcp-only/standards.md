# Standards for PennantPlus v2: API and MCP only

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| architecture/dependencies | vendor/refactor-circus/standards/standards/architecture/dependencies.md | refactor-circus/standards@dev-main | No Atrium reference left in src/config/composer; Keystone stays the only suite require; Scramble stays require-dev |
| architecture/configuration | vendor/refactor-circus/standards/standards/architecture/configuration.md | refactor-circus/standards@dev-main | `pennantplus.atrium` section removed; banners lose dashboard wording |
| architecture/service-providers | vendor/refactor-circus/standards/standards/architecture/service-providers.md | refactor-circus/standards@dev-main | Provider loses the Atrium resolver, Blade conditional, views loading and `pennantplus-views` publish tag |
| architecture/public-api | vendor/refactor-circus/standards/standards/architecture/public-api.md | refactor-circus/standards@dev-main | Removing `@pennantplusManages`, `FeatureValueRow`, `FeatureFlagAccess` and the screen is a breaking change (v2.0.0) |
| architecture/domain-modules | vendor/refactor-circus/standards/standards/architecture/domain-modules.md | refactor-circus/standards@dev-main | The top-level `src/Atrium` directory goes; domains stay as they are |
| http/routes | vendor/refactor-circus/standards/standards/http/routes.md | refactor-circus/standards@dev-main | Existing `pennantplus.*` route names are the documented OpenAPI surface and must not change |
| http/resources | vendor/refactor-circus/standards/standards/http/resources.md | refactor-circus/standards@dev-main | Scramble infers the response schemas from the existing resources |
| http/authorization | vendor/refactor-circus/standards/standards/http/authorization.md | refactor-circus/standards@dev-main | `pennantplus.ability` keeps guarding every endpoint and tool once the page is gone |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | ArchTest drops the `src/Atrium` glob and the `FeatureValueRow` exclusion; gains a no-Atrium rule |
| testing/test-layers | vendor/refactor-circus/standards/standards/testing/test-layers.md | refactor-circus/standards@dev-main | Atrium screen tests go; API, MCP and the new export staleness tests stay wiring-level |
