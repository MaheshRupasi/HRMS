<?php
/**
 * OrangeHRM Enterprise is a closed sourced comprehensive Human Resource Management (HRM)
 * System that captures all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM Inc is the owner of the patent, copyright, trade secrets, trademarks and any
 * other intellectual property rights which subsist in the Licensed Materials. OrangeHRM Inc
 * is the owner of the media / downloaded OrangeHRM Enterprise software files on which the
 * Licensed Materials are received. Title to the Licensed Materials and media shall remain
 * vested in OrangeHRM Inc. For the avoidance of doubt title and all intellectual property
 * rights to any design, new software, new protocol, new interface, enhancement, update,
 * derivative works, revised screen text or any other items that OrangeHRM Inc creates for
 * Customer shall remain vested in OrangeHRM Inc. Any rights not expressly granted herein are
 * reserved to OrangeHRM Inc.
 *
 * Please refer http://www.orangehrm.com/Files/OrangeHRM_Commercial_License.pdf for the license which includes terms and conditions on using this software.
 *
 */

namespace OrangeHRM\Tests\Core\Import;

use DateTime;
use OrangeHRM\Admin\Service\CountryService;
use OrangeHRM\Admin\Service\NationalityService;
use OrangeHRM\Core\Import\PimCsvDataImport;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Country;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Nationality;
use OrangeHRM\Entity\Province;
use OrangeHRM\Framework\Services;
use OrangeHRM\Pim\Dao\EmployeeDao;
use OrangeHRM\Pim\Service\EmployeeService;
use OrangeHRM\Tests\Util\KernelTestCase;

/**
 * @group Core
 * @group Import
 */
class PimCsvDataImportTest extends KernelTestCase
{
    use EntityManagerHelperTrait;

    /**
     * @var PimCsvDataImport
     */
    private PimCsvDataImport $pimCsvDataImport;

    protected function setUp(): void
    {
        $this->pimCsvDataImport = new PimCsvDataImport();
    }

    public function testGetNationalityService(): void
    {
        $this->assertTrue($this->pimCsvDataImport->getNationalityService() instanceof NationalityService);
    }

    public function testGetCountryService(): void
    {
        $mockCountryService = $this->getMockBuilder(CountryService::class)
            ->getMock();

        $this->createKernelWithMockServices(
            [Services::COUNTRY_SERVICE => $mockCountryService]
        );
        $this->assertTrue($this->pimCsvDataImport->getCountryService() instanceof CountryService);
    }

    public function testIsValidPhoneNumber(): void
    {
        $this->assertTrue($this->pimCsvDataImport->isValidPhoneNumber('0702132555'));
        $this->assertNull($this->pimCsvDataImport->isValidPhoneNumber('3wed23r3'));
    }

    public function testImportWhenSuccessful(): void
    {
        $data = [
            "Andrew",
            "",
            "Russel",
            "EMP-003",
            "1992",
            "2343JJ23",
            "2022-10-11",
            "Male",
            "married",
            "American",
            "1992-10-01",
            "1419 Angie Drive",
            "Downward Passage",
            "Burbank",
            "California",
            "91505",
            "United States",
            "714-906-0334",
            "",
            "213-926-2007",
            "yasiru@orangehrmlive.com",
            "yasirun@orangehrmlive.com"
        ];

        $nationality1 = new Nationality();
        $nationality1->setName('American');
        $nationality1->setId(1);
        $this->persist($nationality1);

        $country1 = new Country();
        $country1->setName('UNITED STATES');
        $country1->setCountryName('United States');
        $country1->setCountryCode('US');

        $province1 = new Province();
        $province1->setId(1);
        $province1->setCountryCode('US');
        $province1->setProvinceCode('CA');
        $province1->setProvinceName('California');

        $emailList = array(array('workEmail' => 'abc@example.com', 'otherEmail' => 'cde@example.com'));

        $employee = new Employee();
        $employee->setFirstName('Andrew');
        $employee->setLastName('Russel');
        $employee->setEmployeeId('EMP-003');
        $employee->setBirthday(new DateTime('1992-10-01'));
        $employee->setGender(1);
        $employee->setNationality($nationality1);
        $employee->setMaritalStatus('Married');
        $employee->setOtherId('1992');
        $employee->setDrivingLicenseNo('2343JJ23');
        $employee->setDrivingLicenseExpiredDate(new DateTime('2022-10-11'));
        $employee->setWorkEmail('yasiru@orangehrmlive.com');
        $employee->setOtherEmail('yasirun@orangehrmlive.com');
        $employee->setWorkTelephone('213-926-2007');
        $employee->setHomeTelephone('714-906-0334');
        $employee->setZipcode('91505');
        $employee->setCity('Burbank');
        $employee->setCountry('US');
        $employee->setProvince('CA');
        $employee->setStreet1('1419 Angie Drive');
        $employee->setStreet2('Downward Passage');

        $mockNationalityService = $this->getMockBuilder(NationalityService::class)->getMock();
        $mockNationalityService->expects($this->once())
            ->method('getNationalityByName')
            ->with('American')
            ->will($this->returnValue($nationality1));

        $this->pimCsvDataImport->setNationalityService($mockNationalityService);

        $mockCountryService = $this->getMockBuilder(CountryService::class)
            ->onlyMethods(['getProvinceList'])
            ->getMock();

        $mockCountryService->expects($this->once())
            ->method('getProvinceList')
            ->with()
            ->will($this->returnValue(array($province1)));

        $mockEmployeeService = $this->getMockBuilder(EmployeeService::class)
            ->onlyMethods(['saveEmployee'])
            ->getMock();
        $mockEmployeeService->expects($this->once())
            ->method('saveEmployee')
            ->with($employee)
            ->will($this->returnValue($employee));

        $mockEmployeeDao = $this->getMockBuilder(EmployeeDao::class)
            ->onlyMethods(['getEmailList'])
            ->getMock();
        $mockEmployeeDao->expects($this->exactly(2))
            ->method('getEmailList')
            ->with()
            ->will($this->returnValue($emailList));
        $mockEmployeeService->setEmployeeDao($mockEmployeeDao);

        $this->createKernelWithMockServices(
            [Services::COUNTRY_SERVICE => $mockCountryService, Services::EMPLOYEE_SERVICE => $mockEmployeeService]
        );

        $result = $this->pimCsvDataImport->import($data);

        $this->assertTrue($result);
    }

    public function testImportWhenSuccessfulWhenGenderValueChanges(): void
    {
        $data = [
            "Andrew",
            "",
            "Russel",
            "EMP-003",
            "1992",
            "2343JJ23",
            "2022-10-11",
            "Female",
            "married",
            "American",
            "1992-10-01",
            "1419 Angie Drive",
            "Downward Passage",
            "Burbank",
            "California",
            "91505",
            "United States",
            "714-906-0334",
            "",
            "213-926-2007",
            "yasiru@orangehrmlive.com",
            "yasirun@orangehrmlive.com"
        ];

        $nationality1 = new Nationality();
        $nationality1->setName('American');
        $nationality1->setId(1);
        $this->persist($nationality1);


        $country1 = new Country();
        $country1->setName('UNITED STATES');
        $country1->setCountryName('United States');
        $country1->setCountryCode('US');

        $province1 = new Province();
        $province1->setId(1);
        $province1->setCountryCode('US');
        $province1->setProvinceCode('CA');
        $province1->setProvinceName('California');

        $emailList = array(array('workEmail' => 'abc@example.com', 'otherEmail' => 'cde@example.com'));

        $employee = new Employee();
        $employee->setFirstName('Andrew');
        $employee->setLastName('Russel');
        $employee->setEmployeeId('EMP-003');
        $employee->setBirthday(new DateTime('1992-10-01'));
        $employee->setGender(2);
        $employee->setNationality($nationality1);
        $employee->setMaritalStatus('Married');
        $employee->setOtherId('1992');
        $employee->setDrivingLicenseNo('2343JJ23');
        $employee->setDrivingLicenseExpiredDate(new DateTime('2022-10-11'));
        $employee->setWorkEmail('yasiru@orangehrmlive.com');
        $employee->setOtherEmail('yasirun@orangehrmlive.com');
        $employee->setWorkTelephone('213-926-2007');
        $employee->setHomeTelephone('714-906-0334');
        $employee->setZipcode('91505');
        $employee->setCity('Burbank');
        $employee->setCountry('US');
        $employee->setProvince('CA');
        $employee->setStreet1('1419 Angie Drive');
        $employee->setStreet2('Downward Passage');

        $mockNationalityService = $this->getMockBuilder(NationalityService::class)->getMock();
        $mockNationalityService->expects($this->once())
            ->method('getNationalityByName')
            ->with('American')
            ->will($this->returnValue($nationality1));

        $this->pimCsvDataImport->setNationalityService($mockNationalityService);


        $mockCountryService = $this->getMockBuilder(CountryService::class)
            ->onlyMethods(['getCountryByCountryName', 'getProvinceList'])
            ->getMock();
        $mockCountryService->expects($this->once())
            ->method('getCountryByCountryName')
            ->with('United States')
            ->will($this->returnValue($country1));

        $mockCountryService->expects($this->once())
            ->method('getProvinceList')
            ->with()
            ->will($this->returnValue(array($province1)));

        $mockEmployeeService = $this->getMockBuilder(EmployeeService::class)
            ->onlyMethods(['saveEmployee'])
            ->getMock();

        $mockEmployeeService->expects($this->once())
            ->method('saveEmployee')
            ->with($employee)
            ->will($this->returnValue($employee));

        $this->createKernelWithMockServices(
            [Services::COUNTRY_SERVICE => $mockCountryService, Services::EMPLOYEE_SERVICE => $mockEmployeeService]
        );

        $result = $this->pimCsvDataImport->import($data);

        $this->assertTrue($result);
    }

    public function testImportWhenFirstNameNotExist(): void
    {
        $data = [
            "",
            "",
            "Russel",
            "EMP-003",
            "1992",
            "2343JJ23",
            "2022-10-11",
            "Male",
            "married",
            "American",
            "1992-10-01",
            "1419 Angie Drive",
            "Downward Passage",
            "Burbank",
            "California",
            "91505",
            "United States",
            "714-906-0334",
            "",
            "213-926-2007",
            "yasiru@orangehrmlive.com",
            "yasirun@orangehrmlive.com"
        ];

        $result = $this->pimCsvDataImport->import($data);

        $this->assertFalse($result);
    }

    public function testImportWhenLastNameNotExist(): void
    {
        $data = [
            "Andrew",
            "",
            "",
            "EMP-003",
            "1992",
            "2343JJ23",
            "2022-10-11",
            "Male",
            "married",
            "American",
            "1992-10-01",
            "1419 Angie Drive",
            "Downward Passage",
            "Burbank",
            "California",
            "91505",
            "United States",
            "714-906-0334",
            "",
            "213-926-2007",
            "yasiru@orangehrmlive.com",
            "yasirun@orangehrmlive.com"
        ];

        $result = $this->pimCsvDataImport->import($data);

        $this->assertFalse($result);
    }

}
