#!/bin/bash
touch /tmp/compilation_eibd_in_progress
echo 0 > /tmp/compilation_eibd_in_progress
sudo pkill knxd  
echo 10 > /tmp/compilation_eibd_in_progress
echo "*****************************************************************************************************"
echo "*                                         Remove knxd                                               *"
echo "*****************************************************************************************************"
#sudo apt-get autoremove -qy knxd
cd /usr/local/src/knxd
sudo dpkg -r knxd_*.deb knxd-tools_*.deb
sudo rm -R /usr/local/src/knxd
echo 20 > /tmp/compilation_eibd_in_progress
echo "*****************************************************************************************************"
echo "*                                         Remove eibd                                               *"
echo "*****************************************************************************************************"
sudo rm -R /usr/local/bin/{eibd,knxtool,group*} /usr/local/lib/lib{eib,pthsem}*.so* /usr/local/include/pth*
sudo rm -R /var/log/knxd.log
echo "*****************************************************************************************************"
echo "*                                Installation des dependances                                       *"
echo "*****************************************************************************************************"
sudo apt-get -qy update
sudo apt-get -qy install pkg-config
sudo apt-get -qy install build-essential 
sudo apt-get -qy install libev-dev
sudo apt-get -qy install git-core
sudo apt-get -qy install dpkg-buildpackage
sudo apt-get -qy install cdb
sudo apt-get -qy install git-core
sudo apt-get -qy install debhelper
sudo apt-get -qy install autoconf
sudo apt-get -qy install automake
sudo apt-get -qy install libtool
sudo apt-get -qy install libusb-1.0-0-dev
sudo apt-get -qy install libsystemd-daemon-dev
sudo apt-get -qy install libsystemd-dev
sudo apt-get -qy install dh-systemd
sudo apt-get -qy install cmake
echo 30 > /tmp/compilation_eibd_in_progress
echo "*****************************************************************************************************"
echo "*                                      Installation de KnxD                                         *"
echo "*****************************************************************************************************"
sudo pkill eibd  
sudo pkill knxd  
sudo echo " " > /var/log/knxd.log
sudo chmod 777 /var/log/knxd.log
sudo mkdir /usr/local/src/knxd
cd /usr/local/src/knxd
sudo git clone https://github.com/knxd/knxd.git
cd knxd
git checkout stable
echo 40 > /tmp/compilation_eibd_in_progress
sudo dpkg-buildpackage -b -uc -d
echo 80 > /tmp/compilation_eibd_in_progress
cd /usr/local/src/knxd
sudo dpkg -i knxd_*.deb knxd-tools_*.deb
sudo usermod -a -G dialout knxd
echo 99 > /tmp/compilation_eibd_in_progress
sudo mkdir /etc/eibd/
sudo chmod 777 /etc/eibd/
sudo service knxd stop
sudo update-rc.d knxd disable
sudo chmod 777 /usr/lib/knxd
sudo rm /tmp/compilation_eibd_in_progress
echo "*****************************************************************************************************"
echo "*                                       Installation terminé                                        *"
echo "*****************************************************************************************************"
