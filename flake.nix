{
  description = "Shipnix application config for lineliipers";
  inputs = {
    nixpkgs.url = "nixpkgs/nixos-23.05";
  };

  outputs = { self, nixpkgs, ... } @attrs:
    {
      nixosConfigurations."lineliipers" = nixpkgs.lib.nixosSystem {
        system = "x86_64-linux";
        specialArgs = attrs // {
          environment = "production";
        };
        modules = [
          ./nixos/configuration.nix
        ];
      };
    };
}
    