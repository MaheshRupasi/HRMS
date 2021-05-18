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

namespace OrangeHRM\Admin\Api;

use Exception;
use OrangeHRM\Admin\Api\Model\EducationModel;
use OrangeHRM\Admin\Dto\QualificationEducationSearchFilterParams;
use OrangeHRM\Admin\Service\EducationService;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\Exception\RecordNotFoundException;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Serializer\EndpointCreateResult;
use OrangeHRM\Core\Api\V2\Serializer\EndpointDeleteResult;
use OrangeHRM\Core\Api\V2\Serializer\EndpointGetAllResult;
use OrangeHRM\Core\Api\V2\Serializer\EndpointGetOneResult;
use OrangeHRM\Core\Api\V2\Serializer\EndpointUpdateResult;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Exception\DaoException;
use OrangeHRM\Entity\Education;

class EducationAPI extends EndPoint implements CrudEndpoint
{
    public const PARAMETER_NAME = 'name';
    public const PARAM_RULE_NAME_MAX_LENGTH = 100;

    /**
     * @var null|EducationService
     */
    protected ?EducationService $educationService = null;

    /**
     * @inheritDoc
     * @throws RecordNotFoundException
     * @throws Exception
     */
    public function getOne(): EndpointGetOneResult
    {
        // TODO:: Check data group permission
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $education = $this->getEducationService()->getEducationById($id);
        if (!$education instanceof Education) {
            throw new RecordNotFoundException();
        }
        return new EndpointGetOneResult(EducationModel::class, $education);
    }

    /**
     * @return EducationService
     */
    public function getEducationService(): EducationService
    {
        if (is_null($this->educationService)) {
            $this->educationService = new EducationService();
        }
        return $this->educationService;
    }

    /**
     * @param EducationService $educationService
     */
    public function setEducationService(EducationService $educationService): void
    {
        $this->educationService = $educationService;
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID),
        );
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getAll(): EndpointGetAllResult
    {
        // TODO:: Check data group permission
        $educationParamHolder = new QualificationEducationSearchFilterParams();
        $this->setSortingAndPaginationParams($educationParamHolder);
        $educations = $this->getEducationService()->getEducationList($educationParamHolder);
        $count = $this->getEducationService()->getEducationCount($educationParamHolder);
        return new EndpointGetAllResult(
            EducationModel::class,
            $educations,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count])
        );
    }

    /**
     * @inheritDoc
     * @return ParamRuleCollection
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            ...$this->getSortingAndPaginationParamsRules(QualificationEducationSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }


    /**
     * @inheritDoc
     * @throws Exception
     */
    public function create(): EndpointCreateResult
    {
        // TODO:: Check data group permission
        $educations = $this->saveEducation();
        return new EndpointCreateResult(EducationModel::class, $educations);
    }

    /**
     * @return Education
     * @throws RecordNotFoundException
     * @throws DaoException
     */
    public function saveEducation(): Education
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $name = $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_NAME);
        if (!empty($id)) {
            $education = $this->getEducationService()->getEducationById($id);
            if ($education == null) {
                throw new RecordNotFoundException();
            }
        } else {
            $education = new Education();
        }

        $education->setName($name);
        return $this->getEducationService()->saveEducation($education);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_NAME,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [null, self::PARAM_RULE_NAME_MAX_LENGTH]),
            ),
        );
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function update(): EndpointUpdateResult
    {
        // TODO:: Check data group permission
        $educations = $this->saveEducation();
        return new EndpointUpdateResult(EducationModel::class, $educations);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID,
            new Rule(Rules::POSITIVE)
            ),
            new ParamRule(self::PARAMETER_NAME,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [null, self::PARAM_RULE_NAME_MAX_LENGTH]),
            ),
        );
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForSaveEducation(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID),
            new ParamRule(self::PARAMETER_NAME,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [null, self::PARAM_RULE_NAME_MAX_LENGTH]),
            ),
        );
    }

    /**
     * @return EndpointDeleteResult
     * @throws Exception
     */
    public function delete(): EndpointDeleteResult
    {
        // TODO:: Check data group permission
        $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, CommonParams::PARAMETER_IDS);
        $this->getEducationService()->deleteEducations($ids);
        return new EndpointDeleteResult(ArrayModel::class, $ids);
    }

    /**
     * @inheritDoc
     * @return ParamRuleCollection
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_IDS),
        );
    }
}
