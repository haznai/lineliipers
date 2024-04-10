{ config, pkgs, modulesPath, lib, environment, ... }:
{

  imports = lib.optional (builtins.pathExists ./do-userdata.nix) ./do-userdata.nix ++ [
    (modulesPath + "/virtualisation/digital-ocean-config.nix")
    ./ship.nix

  ];




  swapDevices = [{ device = "/swapfile"; size = 2048; }];



  # Add system-level packages for your server here
  environment.systemPackages = with pkgs; [
    bash
    jc

  ];

  # Loads all environment variables into shell. Remove this if you don't want this enabled
  environment.shellInit = "set -o allexport; source /etc/shipnix/.env; set +o allexport";

  nix.settings.sandbox = false;

  # Automatic garbage collection. Enabling this frees up space by removing unused builds periodically
  nix.gc = {
    automatic = true;
    dates = "weekly";
    options = "--delete-older-than 30d";
  };

  # Saves disk space by detecting and handling identical contents in the Nix Store
  nix.settings.auto-optimise-store = true;

  networking.firewall.enable = true;
  networking.firewall.allowedTCPPorts = [ 22 ];

  programs.vim.defaultEditor = true;

  services.fail2ban.enable = true;

  # This must coincide with the NixOS version used when your first provisioned the server, and then never change even if you upgrade your NixOS version.
  # If you you run this configuration on multiple servers, provisioned with different NixOS versions, you can
  # use the `environment` value that is passed through `specialArgs` from the flake.nix and differentiate so it's correct for each server
  # for example `system.stateVersion = if environment == "production" then "23.05" else "22.11";` or whatever fits your case 
  system.stateVersion = "23.05";
}

