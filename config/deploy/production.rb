set :stage, :production
server '46.248.188.97', user: 'zapytaj', port: '7324', roles: %w(app db web notify)
set :deploy_to, "/home/zapytaj"
set :user, 'zapytaj'

ask :branch, proc { `git describe --tags $(git rev-list --tags --max-count=1)`.chomp }
