# Shipnix recommended settings
# IMPORTANT: These settings are here for ship-nix to function properly on your server
# Modify with care

{ config, pkgs, modulesPath, lib, ... }:
{
  nix = {
    package = pkgs.nixUnstable;
    extraOptions = ''
      experimental-features = nix-command flakes ca-derivations
    '';
    settings = {
      trusted-users = [ "root" "ship" "nix-ssh" ];
    };
  };

  programs.git.enable = true;
  programs.git.config = {
    advice.detachedHead = false;
  };

  services.openssh = {
    enable = true;
    # ship-nix uses SSH keys to gain access to the server
    # Manage permitted public keys in the `authorized_keys` file
    settings.PasswordAuthentication = false;
  };


  users.users.ship = {
    isNormalUser = true;
    extraGroups = [ "wheel" "nginx" ];
    # If you don't want public keys to live in the repo, you can remove the line below
    # ~/.ssh will be used instead and will not be checked into version control. 
    # Note that this requires you to manage SSH keys manually via SSH,
    # and your will need to manage authorized keys for root and ship user separately
    openssh.authorizedKeys.keyFiles = [ ./authorized_keys ];
    openssh.authorizedKeys.keys = [
      "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQCk6cTMFQYh1NRaPMf9IuSDbSwAESl07jGlSYsRv7dG64pCBhAdzQ6VXJs6B/dPCbVu7Ew6SpUCESrAsslIHIbwv7uxQy8pzFUAvsnzoaw1ZiNvV+rk1zidX7Rr+PzORniCWRABouSn2c6vns0M6icdVcLpOdOkbG/1BGe+AY8EugvvbTYVbO/ShlPi+zCCLpBa8e7eGh1YQovQSulcQBy2YO9u8uu1vtbF73eihwSmugYCAR5STHI8BOn90f3j5bVQw///9irNNdhTCAFdsHSADqyaSCuKD00O89WhyI3Dnm8ebxY+vHlqYnd7FgqMx8N9zCkjn/8GvFKEqI6j/9g8NZ+/iEHzzec3CDzL+/5Ih5mhFstQE/t8crE6PzMW+mEYokqakHbZfd3v/fmH9idQBn+t0O4AkqAH4V0SqH89GgsxPmPLP5AKEilJ+zUWfek8yUlFXO53gnZS6Al5wWSooK1J4SFq5xLKEeHGPDqKD6XT6d3QZL5ROl+LN0tdlz8= ship@tite-ship
"
    ];
  };

  # Can be removed if you want authorized keys to only live on server, not in repository
  # Se note above for users.users.ship.openssh.authorizedKeys.keyFiles
  users.users.root.openssh.authorizedKeys.keyFiles = [ ./authorized_keys ];
  users.users.root.openssh.authorizedKeys.keys = [
    "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQCk6cTMFQYh1NRaPMf9IuSDbSwAESl07jGlSYsRv7dG64pCBhAdzQ6VXJs6B/dPCbVu7Ew6SpUCESrAsslIHIbwv7uxQy8pzFUAvsnzoaw1ZiNvV+rk1zidX7Rr+PzORniCWRABouSn2c6vns0M6icdVcLpOdOkbG/1BGe+AY8EugvvbTYVbO/ShlPi+zCCLpBa8e7eGh1YQovQSulcQBy2YO9u8uu1vtbF73eihwSmugYCAR5STHI8BOn90f3j5bVQw///9irNNdhTCAFdsHSADqyaSCuKD00O89WhyI3Dnm8ebxY+vHlqYnd7FgqMx8N9zCkjn/8GvFKEqI6j/9g8NZ+/iEHzzec3CDzL+/5Ih5mhFstQE/t8crE6PzMW+mEYokqakHbZfd3v/fmH9idQBn+t0O4AkqAH4V0SqH89GgsxPmPLP5AKEilJ+zUWfek8yUlFXO53gnZS6Al5wWSooK1J4SFq5xLKEeHGPDqKD6XT6d3QZL5ROl+LN0tdlz8= ship@tite-ship
"
  ];

  security.sudo.extraRules = [
    {
      users = [ "ship" ];
      commands = [
        {
          command = "ALL";
          options = [ "NOPASSWD" "SETENV" ];
        }
      ];
    }
  ];
}
