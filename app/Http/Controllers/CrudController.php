<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Connection\Bitrix;

class CrudController extends Controller
{
    const BITRIX_AUTH = 0;
    const BITRIX_SELECT_ALL = ['select' => ["*", "EMAIL"]];

    public function verify(Request $request)
    {
        return response('', 302)->header('Location', env('FRONTEND_URL'));
    }

    public function getCompanies(Request $request)
    {
        $companies = $this->getAllCompanies();
        $contacts = $this->getAllContacts();

        foreach ($contacts['result'] as $contact) {
            $found = array_search((string) $contact['COMPANY_ID'], array_column($companies['result'], 'ID'));
            if ($found !== false) {
                if (!isset($companies['result'][$found]['contacts'])) {
                    $companies['result'][$found]['contacts'] = [];
                }
                $companies['result'][$found]['contacts'][] = $contact;
            }
        }

        return response()->json($companies, 200);
    }

    public function getContacts(Request $request)
    {
        $contacts = $this->getAllContacts();
        return response()->json($contacts, 200);
    }

    public function createCompany(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string',
            'email' => 'required|email',
            'contact_name_1' => 'nullable|string',
            'contact_second_name_1' => 'nullable|string',
            'contact_name_2' => 'nullable|string',
            'contact_second_name_2' => 'nullable|string',
        ]);

        $company = $this->handleBitrixResponse($this->callBitrixAPI(
            'crm.company.add',
            ['fields' => [
                'TITLE' => $validated['company_name'],
                'EMAIL' => [['VALUE' => $validated['email'], 'VALUE_TYPE' => 'WORK']],
            ]]
        ));

        $this->createAndLinkContact($validated['contact_name_1'], $validated['contact_second_name_1'], $company);
        $this->createAndLinkContact($validated['contact_name_2'], $validated['contact_second_name_2'], $company);

        return response()->json($company, 200);
    }

    public function editCompany(Request $request, $id)
    {
        $validated = $request->validate([
            'company_name' => 'required|string',
            'email' => 'required|email',
            'contact_1_id' => 'nullable|integer',
            'contact_name_1' => 'nullable|string',
            'contact_second_name_1' => 'nullable|string',
            'contact_2_id' => 'nullable|integer',
            'contact_name_2' => 'nullable|string',
            'contact_second_name_2' => 'nullable|string',
        ]);

        $this->callBitrixAPI('crm.company.update', [
            'ID' => $id,
            'fields' => [
                'TITLE' => $validated['company_name'],
                'EMAIL' => [['VALUE' => $validated['email'], 'VALUE_TYPE' => 'WORK']],
            ],
        ]);

        $this->updateOrCreateContact($validated['contact_1_id'], $validated['contact_name_1'], $validated['contact_second_name_1'], $id);
        $this->updateOrCreateContact($validated['contact_2_id'], $validated['contact_name_2'], $validated['contact_second_name_2'], $id);

        return response()->json(['message' => 'Company updated successfully'], 200);
    }

    public function deleteCompany(Request $request, $id)
    {
        $this->callBitrixAPI('crm.contact.delete', ['ID' => $request->contact_1_id]);
        $this->callBitrixAPI('crm.contact.delete', ['ID' => $request->contact_2_id]);
        $this->callBitrixAPI('crm.company.delete', ['ID' => $id]);

        return response('', 200);
    }


    private function callBitrixAPI(string $url, array $data = [], int $auth = self::BITRIX_AUTH)
    {
        $query = http_build_query($data);
        $result = Bitrix::ConnectionBitrix($query, $url, $auth);
        return json_decode($result, true);
    }

    private function handleBitrixResponse(array $response)
    {
        if (!isset($response['result'])) {
            throw new \Exception('Error in Bitrix response');
        }
        return $response['result'];
    }

    private function getAllCompanies()
    {
        $companies = $this->callBitrixAPI('crm.company.list', self::BITRIX_SELECT_ALL);
        return isset($companies['result']) ? $companies : ['result' => []];
    }

    private function getAllContacts()
    {
        $contacts = $this->callBitrixAPI('crm.contact.list', self::BITRIX_SELECT_ALL);
        return isset($contacts['result']) ? $contacts : ['result' => []];
    }

    private function mergeContactsWithCompanies(array $contacts, array $companies)
    {
        foreach ($contacts['result'] as $contact) {
            $found = array_search((string) $contact['COMPANY_ID'], array_column($companies['result'], 'ID'));
            if ($found !== false) {
                if (!isset($companies['result'][$found]['contacts'])) {
                    $companies['result'][$found]['contacts'] = [];
                }
                $companies['result'][$found]['contacts'][] = $contact;
            }
        }
        return $companies;
    }

    private function createAndLinkContact($name, $lastName, $id)
    {
        if (!$name) {
            return;
        }

        $contact = $this->handleBitrixResponse($this->callBitrixAPI(
            'crm.contact.add',
            ['fields' => [
                'NAME' => $name,
                'LAST_NAME' => $lastName,
            ]]
        ));

        $this->callBitrixAPI('crm.company.contact.add', [
            'ID' => $id,
            'fields' => [
                'CONTACT_ID' => $contact,
            ],
        ]);
    }

    private function updateOrCreateContact($idContact, $name, $lastName, $idCompany)
    {
        if ($idContact) {
            $this->callBitrixAPI('crm.contact.update', [
                'ID' => $idContact,
                'fields' => [
                    'NAME' => $name,
                    'LAST_NAME' => $lastName,
                ],
            ]);
        } else if ($name) {
            $this->createAndLinkContact($name, $lastName, $idCompany);
        }
    }
}
