# -*- mode: ruby -*-
# vi: set ft=ruby :
Vagrant.configure(2) do |config|
  config.vm.box = "hashicorp/precise32"
  config.vm.provision :shell, path: "vagrant_bootstrap.sh"
  config.vm.network :forwarded_port, guest: 80, host: 1472
end
