<?php

namespace Application\Repository;

use Doctrine\ORM\EntityRepository;

class ParticipantRepository
    extends EntityRepository
{
    public function countAll()
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @return array
     */
    public function getByHours()
    {
        $results = array();

        $databaseResults = $this->getEntityManager()
            ->createQuery(
                "SELECT
                    TIMESTAMP(CONCAT(
                        DATE(p.timeCreated),
                        ' ',
                        HOUR(p.timeCreated),
                        ':00:00'
                    )) AS date,
                    COUNT(p.id) AS countNumber
                FROM Application\Entity\ParticipantEntity p
                GROUP BY date
                ORDER BY date DESC"
            )
            ->getArrayResult()
        ;

        $firstDate = date('Y-m-d H:00:00', strtotime('-2 days'));
        $lastDate = date('Y-m-d H:00:00');
        $dates = dateRange($firstDate, $lastDate, '+ 1 hour', 'Y-m-d H:00:00');

        foreach ($dates as $date) {
            $count = 0;

            if ($databaseResults) {
                foreach($databaseResults as $databaseResult) {
                    if($databaseResult['date'] == $date) {
                        $count = (int) $databaseResult['countNumber'];
                    }
                }
            }

            $results[] = array(
                'date' => $date,
                'count' => $count,
            );
        }

        return $results;
    }

    /**
     * @return array
     */
    public function getByDays()
    {
        $results = array();

        $databaseResults = $this->getEntityManager()
            ->createQuery(
                "SELECT
                    DATE(p.timeCreated) AS date,
                    COUNT(p.id) AS countNumber
                FROM Application\Entity\ParticipantEntity p
                GROUP BY date
                ORDER BY date DESC"
            )
            ->getArrayResult()
        ;

        $firstDate = date('Y-m-d', strtotime('-4 weeks'));
        $lastDate = date('Y-m-d');
        $dates = dateRange($firstDate, $lastDate, '+ 1 day', 'Y-m-d');

        foreach ($dates as $date) {
            $count = 0;

            if ($databaseResults) {
                foreach($databaseResults as $databaseResult) {
                    if($databaseResult['date'] == $date) {
                        $count = (int) $databaseResult['countNumber'];
                    }
                }
            }

            $results[] = array(
                'date' => $date,
                'count' => $count,
            );
        }

        return $results;
    }

    /**
     * @return array
     */
    public function getByBrowsers($app)
    {
        $data = array(
            'Chrome' => 0,
            'Firefox' => 0,
            'Opera' => 0,
            'Safari' => 0,
            'IE' => 0,
        );

        $databaseResults = $this->getEntityManager()
            ->createQuery(
                "SELECT
                    p.userAgentUa,
                    COUNT(p.id) AS countNumber
                FROM Application\Entity\ParticipantEntity p
                GROUP BY p.userAgentUa"
            )
            ->getArrayResult()
        ;

        if ($databaseResults) {
            foreach ($databaseResults as $databaseResult) {
                $userAgentUa = $databaseResult['userAgentUa'];

                $data[$userAgentUa] = $databaseResult['countNumber'];
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getByOperatingSystems($app)
    {
        $data = array(
            'Windows' => 0,
            'Mac OS X' => 0,
            'Linux' => 0,
        );

        $databaseResults = $this->getEntityManager()
            ->createQuery(
                "SELECT
                    p.userAgentOs,
                    COUNT(p.id) AS countNumber
                FROM Application\Entity\ParticipantEntity p
                GROUP BY p.userAgentOs"
            )
            ->getArrayResult()
        ;

        if ($databaseResults) {
            foreach ($databaseResults as $databaseResult) {
                $userAgentOs = $databaseResult['userAgentOs'];

                $data[$userAgentOs] = $databaseResult['countNumber'];
            }
        }

        return $data;
    }
}
