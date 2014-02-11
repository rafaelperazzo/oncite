cd /var/www/oncite/
cp atualizar.sh /var/www/oncite/scripts/
now=$(date '+%d/%m/%Y %H:%M')
git add /var/www/oncite/scripts/
git commit -a -m "Version $now"
git push origin master --force
