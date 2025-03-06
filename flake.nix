{
  inputs = {
    nixpkgs.url = "github:nixos/nixpkgs/nixos-unstable";
    flake-parts.url = "github:hercules-ci/flake-parts";
    flake-compat.url = "github:nix-community/flake-compat";
  };

  outputs =
    inputs@{ flake-parts, ... }:
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
          inherit (pkgs) mkShell git;

          php = pkgs.php84;

          devPackages =
            [
              git
              php
              php.packages.composer
            ]
            ++ (with pkgs; [
              curl
              jq
              just
              watchexec
            ]);
        in
        {

          devShells.default = mkShell {
            packages = devPackages;

            shellHook = ''
              export WORKSPACE_ROOT="$(${git}/bin/git rev-parse --show-toplevel)";
              export PATH="$WORKSPACE_ROOT/vendor/bin:$WORKSPACE_ROOT/node_modules/bin:$PATH";
              export COMPOSER_CACHE_DIR="$WORKSPACE_ROOT/.composer/cache";
            '';
          };
        };
    };
}
