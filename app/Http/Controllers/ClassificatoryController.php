<?php

namespace App\Http\Controllers;

use App\ClassModel;
use App\ClassParam;
use App\Param;
use App\Value;
use App\Chemical_subs_possible_formules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassificatoryController extends Controller
{
    function main () {
        $allParams = Param::all();

        return view('welcome', [
            "params" => $allParams
        ]);
    }

    function classesPage () {
        $allClsPrms = ClassParam::select()
            ->with('param', 'value', '_class')
            ->get()
            ->groupBy("class_id");

        $allParams = Param::all();

        return view('classes', [
            "classes" => $allClsPrms,
            "params" => $allParams
        ]);
    }

    function paramsPage () {
        $allParams = Param::all();

        return view('params', [
            "params" => $allParams
        ]);
    }

    function addClass (Request $request) {
        $newClassName = strtolower($request->class_name);

        $newClass = new ClassModel;
        $newClass->name = $newClassName;
        $newClass->save();

        $allParams = Param::all();

        foreach ($allParams as $param) {
            $newValue = new Value;
            $newValue->min = 0;
            $newValue->max = 0;
            $newValue->save();

            $newClassParam = new ClassParam();
            $newClassParam->class_id = $newClass->id;
            $newClassParam->value_id = $newValue->id;
            $newClassParam->param_id = $param->id;

            $newClassParam->save();
        }

        return $newClassName;
    }

    function delClass(Request $request) {
        $newClassName = strtolower($request->class_name);
        $newClass = new ClassModel;
        $newClass->name = $newClassName;;
        $classId = ClassModel::where('name', $newClass->name)->pluck('id');
        $classParamId = ClassParam::where('class_id', $classId)->pluck('id');
        $classParamValueId = ClassParam::where('class_id', $classId)->pluck('value_id');

        // Удаляем параметры класса
        for($i = 0; $i < count($classParamId); $i++) {
            ClassParam::where('id', '=', $classParamId[$i])->delete();
        }

        // Удаляем класс
        ClassModel::where('name', '=', $newClass->name)->delete();

        // Удаляем значения
        for($i = 0; $i < count($classParamValueId); $i++) {
            Value::where('id', '=', $classParamValueId[$i])->delete();
        }

        return $newClassName;
    }

    function addParam (Request $request) {
        $newParamName = $request->param_name;

        $newParam = new Param;
        $newParam->name = $newParamName;
        $newParam->save();

        $allClasses = ClassModel::all();

        foreach ($allClasses as $class) {
            $newValue = new Value;
            $newValue->min = 0;
            $newValue->max = 0;
            $newValue->save();

            $newClassParam = new ClassParam();
            $newClassParam->class_id = $class->id;
            $newClassParam->value_id = $newValue->id;
            $newClassParam->param_id = $newParam->id;

            $newClassParam->save();
        }

        return $newParamName;
    }

    function delParam (Request $request) {
        $newParamName = strtolower($request->param_name);

        $param = Param::select("id")
            ->where('name', $newParamName)
            ->first();

        $classParams = ClassParam::select("id", "value_id")
            ->where('param_id', $param->id)
            ->get();

        foreach ($classParams as $classParam) {
            ClassParam::where('id', $classParam->id)->delete();
            Value::where('id', $classParam->value_id)->delete();
        }

        $param->delete();


        return $newParamName;
    }

    function updateClassParam (Request $request) {
        $value_id = $request->value_id;

        $editingValue = Value::where('id', $value_id)->first();

        $editingValue->min = $request->min;
        $editingValue->max = $request->max;

        $editingValue->save();

        return "Ok";
    }

    function computeClass (Request $request) {
        $all_fields = $request->all();
        unset($all_fields["_token"]);

        foreach ($all_fields as $key => $value) {
            if (is_null($value))
                unset($all_fields[$key]);
        }

        $computedClsPrmSet = ClassParam::where('id', '>', '0')
            ->with('param', 'value', '_class')
            ->get();

        $suitableClassesParams = [];

        foreach ($computedClsPrmSet as $currClsPrm) {
            foreach ($all_fields as $key => $value) {
                if (
                    $currClsPrm->param->name == $key &&
                    $currClsPrm->value->min <= $value &&
                    $currClsPrm->value->max >= $value &&
                    !is_null($value)
                )
                    array_push($suitableClassesParams, $currClsPrm);
            }
        }

        $totalParamsCount = count($all_fields);

        $classesSuitableParamsCount = [];

        foreach ($suitableClassesParams as $clsParam) {
            $clsId = $clsParam->_class->id;

            if (!isset($classesSuitableParamsCount["$clsId"]["count"])) {
                $classesSuitableParamsCount["$clsId"]["count"] = 1;
                $classesSuitableParamsCount["$clsId"]["name"] = $clsParam->_class->name;
            } else
                $classesSuitableParamsCount["$clsId"]["count"]++;
        }

        $resultClasses = [];

        foreach ($classesSuitableParamsCount as $key => $value) {
            if ($value["count"]== $totalParamsCount)
                array_push($resultClasses, $value["name"]);
        }

        return $resultClasses;
    }

    function addSubsPossibleFormula (Request $request) {
        $newFormulaText = $request->formula;

        //$newFormula = new Chemical_subs_possible_formules;
        //$newFormula->name = $newFormulaText;
        //$newFormula->save();

        $testString = "";
        for ($i = 0; $i < strlen($newFormulaText); $i++) {
            $testString .= $newFormulaText{$i};
        }

        return $testString;
    }
}
