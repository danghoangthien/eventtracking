days_not_restore=(20170505 20170506)
days_not_delete=(20170504)
today=$(date "+%Y%m%d")
#tomorrow=$(date -d "$today next day" +%Y%m%d)
#check if today is holiday
is_holiday=0
for i in "${days_not_restore[@]}" ; do
    if [ "$i" -eq "$today" ] ; then
        is_holiday=1
        break
    fi
done
if [ $is_holiday -eq 1 ] ; then
    echo "Sorry, you cannot run this script today, because today is holiday"
    exit
fi
is_delete=1
for i in "${days_not_delete[@]}" ; do
    if [ "$i" -eq "$today" ] ; then
        is_delete=0
        break
    fi
done
#check if today is weekend
if [ $(date +%u) -gt 5 ] && [ $is_delete -eq 1 ] ; then
    echo "Sorry, you cannot run this script today, because today is weekend"
    exit
fi
hour=$(date "+%H")
#restore cluster from snapshot
if [ $(date +%H) -eq 9 ] ; then
    echo "restore cluster from snapshot"
    #aws redshift restore-from-cluster-snapshot --cluster-identifier testcluster --snapshot-identifier testcluster
    exit
fi
#delete cluster and create snapshot at 7pm
if [  $(date +%H) -eq 19 ] && [ $is_delete -eq 1 ] ; then
    echo "delete cluster and create snapshot at 7pm"
    #aws redshift delete-cluster-snapshot --snapshot-identifier testcluster
    #aws redshift delete-cluster --cluster-identifier testcluster --final-cluster-snapshot-identifier testcluster
    exit
fi
#delete cluster and create snapshot when is_delete = 0
if [  $(date +%H) -eq 23 ] && [ $is_delete -eq 0 ] ; then
    echo "delete cluster and create snapshot at 11pm"
    #aws redshift delete-cluster-snapshot --snapshot-identifier testcluster
    #aws redshift delete-cluster --cluster-identifier testcluster --final-cluster-snapshot-identifier testcluster
    exit
fi

echo "do nothing"