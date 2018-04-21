<?php

/**
 * This class adds a RealURL configuration.
 *
 * @author     Oliver Klee <typo3-coding@oliverklee.de>
 */
class Tx_Realty_Configuration_RealUrl_Configuration
{
    /**
     * Adds RealURL configuration.
     *
     * @param mixed[][] $parameters the RealUrl configuration to modify
     *
     * @return mixed[][] the modified RealURL configuration
     */
    public function addConfiguration(array $parameters)
    {
        $preVariables = [
            [
                'GETvar' => 'no_cache',
                'valueMap' => [
                    'no_cache' => 1,
                ],
                'noMatch' => 'bypass',
            ],
        ];

        $paginationGetVariable = [
            'GETvar' => 'tx_realty_pi1[pointer]',
            'valueMap' => [
                '1' => '0',
                '2' => '1',
                '3' => '2',
                '4' => '3',
                '5' => '4',
                '6' => '5',
                '7' => '6',
                '8' => '7',
                '9' => '8',
                '10' => '9',
                '11' => '10',
                '12' => '11',
                '13' => '12',
                '14' => '13',
                '15' => '14',
                '16' => '15',
                '17' => '16',
                '18' => '17',
                '19' => '18',
                '20' => '19',
                '21' => '20',
                '22' => '21',
                '23' => '22',
                '24' => '23',
                '25' => '24',
                '26' => '25',
                '27' => '26',
                '28' => '27',
                '29' => '28',
                '30' => '29',
                '31' => '30',
            ],
            'noMatch' => 'bypass',
        ];

        $realtyObjectGetVariable = [
            'GETvar' => 'tx_realty_pi1[showUid]',
            'lookUpTable' => [
                'table' => 'tx_realty_objects',
                'id_field' => 'uid',
                'alias_field' => 'title',
                'addWhereClause' => ' AND NOT deleted',
                'useUniqueCache' => true,
                'useUniqueCache_conf' => [
                    'strtolower' => 1,
                    'spaceCharacter' => '-',
                ],
                'autoUpdate' => true,
            ],
        ];

        $modeGetVariable = ['GETvar' => 'tx_realty_pi1[mode]', 'valueMap' => ['-' => '0']];
        $removeGetVariable = ['GETvar' => 'tx_realty_pi1[remove]', 'valueMap' => ['remove' => '1', '-' => '0']];
        $deleteGetVariable = ['GETvar' => 'tx_realty_pi1[delete]', 'valueMap' => ['remove' => '1', '-' => '0']];
        $ownerGetVariable = ['GETvar' => 'tx_realty_pi1[owner]', 'valueMap' => ['-' => '0']];
        $uidGetVariable = ['GETvar' => 'tx_realty_pi1[uid]', 'valueMap' => ['-' => '0']];

        return array_merge_recursive(
            $parameters['config'],
            [
                'preVars' => $preVariables,
                'postVarSets' => [
                    '_DEFAULT' => [
                        'immo' => [
                            $paginationGetVariable, $realtyObjectGetVariable, $modeGetVariable, $removeGetVariable,
                            $deleteGetVariable, $ownerGetVariable, $uidGetVariable,
                        ],
                    ],
                ],
            ]
        );
    }
}
