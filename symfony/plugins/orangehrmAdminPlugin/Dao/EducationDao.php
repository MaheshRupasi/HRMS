<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

namespace OrangeHRM\Admin\Dao;
use Doctrine\ORM\Tools\Pagination\Paginator;
use OrangeHRM\Core\Exception\DaoException;
use Doctrine\ORM\QueryBuilder;
use Exception;
use OrangeHRM\ORM\Doctrine;
use OrangeHRM\ORM\DoctrineQuery;
use OrangeHRM\Entity\Education;
use OrangeHRM\ORM\ListSorter;



class EducationDao {



    /**
     * @param Education $education
     * @return Education
     * @throws \DaoException
     */

    public function saveEducation(Education $education) :Education
    {
        
        try {

            Doctrine::getEntityManager()->persist($education);
            Doctrine::getEntityManager()->flush();
            //Doctrine::getEntityManager()->setParameter(); //test code
            return $education;
        } catch (Exception $e) {
            throw new \DaoException($e->getMessage(), $e->getCode(), $e);
        }
        
    }

    /**
     * get education names according to the Id
     * @param int $id
     * @return Education|null
     * @throws DaoException
     */
    // completed please double check
    public function getEducationById(int $id): ?Education
    {

        try {
            $education = Doctrine::getEntityManager()->getRepository(Education::class)->find($id);
            if ($education instanceof Education) {
                return $education;
            }
            return null;
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }

    }

    /**
     * @param $name
     * @return Education|null
     * @throws DaoException
     */

    // double check on that
    public function getEducationByName(string $name): ?Education
    {
        try {
            $query = Doctrine::getEntityManager()->getRepository(
                Education::class
            )->createQueryBuilder('e');
            $query->andWhere('e.name = :name');
            $query->setParameter('name', $name);
            return $query->getQuery()->getOneOrNullResult();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
        
    }



    /**
     * @param string $sortField
     * @param string $sortOrder
     * @param null $limit
     * @param null $offset
     * @param false $count
     * @return int|mixed|string
     * @throws DaoException
     */
    
    public function getEducationList(
        $sortField = 'jc.name',
        $sortOrder = 'ASC',
        $limit = null,
        $offset = null,
        $count = false

    ) {
        $sortField = ($sortField == "") ? 'jc.name' : $sortField;
        $sortOrder = strcasecmp($sortOrder, 'DESC') === 0 ? 'DESC' : 'ASC';
        try {
            $q = Doctrine::getEntityManager()->getRepository(Education::class)->createQueryBuilder('jc');
            $q->addOrderBy($sortField, $sortOrder);
            if (!empty($limit)) {
                $q->setFirstResult($offset)
                    ->setMaxResults($limit);
            }

            if ($count) {
                $paginator = new Paginator($q, true);
                return count($paginator);
            }
            return $q->getQuery()->execute();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }


        
    }

    /**
     * Soft Delete Education field
     * @param array $toDeleteIds
     * @return int
     * @throws DaoException
     */
    
    public function deleteEducations(array $toDeleteIds) :int

    {
        try {
            $q = Doctrine::getEntityManager()->createQueryBuilder();
            $q->delete(Education::class, 'E')
                ->set('E.deleted', true)
                ->where($q->expr()->in('E.id', $toDeleteIds));

            return $q->getQuery()->execute();

            
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }        
        
    }
    // coundn't resolve this

    /**
     * @param $educationName
     * @return bool
     * @throws DaoException
     */
    public function isExistingEducationName($educationName) {


        try {

            $q = Doctrine_Query:: create()->from('Education l')
                            ->where('l.name = ?', trim($educationName));

            if ($q->count() > 0) {
                return true;
            }

            return false;

        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
        
    }

}