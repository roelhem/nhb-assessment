{
  inputs = {
    nixpkgs.url = "github:nixos/nixpkgs/nixos-unstable";
    flake-parts.url = "github:hercules-ci/flake-parts";
    flake-compat.url = "github:nix-community/flake-compat";
  };

  outputs =
    inputs@{
      self,
      nixpkgs,
      flake-parts,
      ...
    }:
    flake-parts.lib.mkFlake { inherit inputs; } {
      systems = [
        "x86_64-linux"
        "aarch64-linux"
        "x86_64-darwin"
        "aarch64-darwin"
      ];

      perSystem =
        {
          config,
          self',
          pkgs,
          php,
          lib,
          ...
        }:
        let
          inherit (pkgs)
            mkShell
            git
            watchexec
            dockerTools
            ;

          curlCerts = pkgs.fetchurl {
            url = "https://curl.se/ca/cacert-2025-02-25.pem";
            hash = "sha256-UKYnfsaRE/AMX9RfCei5eks+MtqjXTqVqzATelU4bO8=";
          };

          php = pkgs.php84.buildEnv {
            extensions =
              { enabled, all }:
              with all;
              [
                dom
                curl
                ctype
                openssl
                filter
                iconv
                bcmath
                pcntl
                posix
                tokenizer
                intl
                ast
                fileinfo
                session
                simplexml
                xmlwriter
                zlib
              ];
            extraConfig = ''
              curl.cainfo = ${curlCerts}
            '';
          };

          phpDev = php.override {
            cgiSupport = false;
            fpmSupport = false;
            phpdbgSupport = false;
            argon2Support = false;
            systemdSupport = false;
            valgrindSupport = false;
          };

          nhb-assessment = phpDev.buildComposerProject (final: {
            pname = "nhb-assessment";
            version = "0.1.0";
            src = ./.;
            vendorHash = "sha256-+vx0QcGo+9X75v8WfJTU4c/9FRUufbcl0oxSm7KOnWs=";
          });
        in
        {

          packages = {
            inherit nhb-assessment;

            docker-image = dockerTools.buildImage {
              name = "roelhem/nhb-assessment";
              tag = "latest";

              config = {
                Entrypoint = [
                  "${self.packages."x86_64-linux".nhb-assessment}/bin/nhb-assessment"
                ];
                Cmd = [
                  "init"
                  "/data/__test.ini"
                ];
              };
            };
          };

          devShells.default = mkShell {
            packages = [
              git
              php
              php.packages.composer
              watchexec
            ];

            shellHook = ''
              export WORKSPACE_ROOT="$(${git}/bin/git rev-parse --show-toplevel)";
              export PATH="$WORKSPACE_ROOT/vendor/bin:$WORKSPACE_ROOT/node_modules/bin:$PATH";
              export COMPOSER_CACHE_DIR="$WORKSPACE_ROOT/.composer/cache";
            '';
          };
        };
    };
}
