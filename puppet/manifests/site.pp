$env = 'dev'
#$env = 'prod'
$user = 'ubuntu'
$group = 'ubuntu'
$home = '/home/ubuntu'
$www = '/vagrant/web'
$project = 'base'
# fpm
$fpm_allowed_clients = '127.0.0.1'

Exec { path => "/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin" }
File { owner => $user, group => $group }

node 'default' {
  include nginx
  include php
  #include mysql
}
