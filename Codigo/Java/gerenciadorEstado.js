// Gerenciador de estado


import { CONFIG } from "./configuracao.js";



export const StateManager = {



state:{


    renda:0,


    gastos:[],


    numPessoas:1,


    viveSozinho:true,


    idUsuario:1,


    mesReferencia:null



},



// Datas


getCurrentMonthKey(){


    const agora = new Date();


    return (

        agora.getFullYear()
        +
        "-"
        +
        String(agora.getMonth()+1)
        .padStart(2,"0")

    );


},



getMonthStart(){


    return this.getCurrentMonthKey()+"-01";


},



getMonthEnd(){


    const agora=new Date(

        new Date().getFullYear(),

        new Date().getMonth()+1,

        0

    );


    return agora.toISOString()
    .split("T")[0];


},



isCurrentMonth(data){


    if(!data)
        return false;


    return String(data)
    .substring(0,7)
    ===
    this.getCurrentMonthKey();


},



// Inicialização


init(){


const mesAtual=this.getCurrentMonthKey();



let salvo=
localStorage.getItem(CONFIG.STORAGE_KEY);



if(!salvo){


    // Tenta recuperar a versão antiga

    for(const chave of CONFIG.OLD_STORAGE_KEYS){


        const antigo=
        localStorage.getItem(chave);


        if(antigo){

            salvo=antigo;

            break;

        }


    }


}



if(salvo){


try{


const dados=JSON.parse(salvo);



if(
dados.mesReferencia &&
dados.mesReferencia !== mesAtual
){


this.state={


    ...this.state,


    renda:0,


    gastos:[],


    numPessoas:
    dados.numPessoas ?? 1,


    viveSozinho:
    dados.viveSozinho ?? true,


    idUsuario:
    dados.idUsuario ?? 1,


    mesReferencia:
    mesAtual



};



}else{


this.state={


    ...this.state,


    ...dados,


    mesReferencia:mesAtual


};



}



}catch(e){



console.error(
"Erro carregando estado",
e
);


this.reset();



}



}else{


this.state.mesReferencia=mesAtual;


}



this.save();



},





// Salvar


save(){


try{


localStorage.setItem(

CONFIG.STORAGE_KEY,

JSON.stringify(this.state)

);



}catch(e){


console.error(
"Erro salvando estado",
e
);


}



},




// Leitura


get(){


return this.state;


},




// Alteração


set(chave,valor){



if(chave==="renda"){

valor=Math.max(
0,
Number(valor)||0
);

}



if(chave==="numPessoas"){

valor=Math.max(
1,
parseInt(valor)||1
);

}



if(chave==="viveSozinho"){

valor=Boolean(valor);

}



this.state[chave]=valor;


this.save();



},




update(obj={}){


Object.keys(obj)
.forEach(chave=>{


this.set(
chave,
obj[chave]
);


});



},




// Gastos


setGastos(lista){



if(!Array.isArray(lista))
return;



this.state.gastos=
lista.map(g=>({


    ...g,


    valor:
    Number(g.valor)||0



}));


this.save();



},





// Cálculos


getCustoPorPessoa(valor){



const pessoas =
Math.max(
1,
this.state.numPessoas
);



return Number(valor)/pessoas;



},




getTotalGastos(){



return this.state.gastos
.reduce(

(total,g)=>

total+
(Number(g.valor)||0),

0

);



},




getTotalPorPessoa(){



return this.getTotalGastos()
/
Math.max(
1,
this.state.numPessoas
);



},




// Reset


reset(){



this.state={


renda:0,


gastos:[],


numPessoas:1,


viveSozinho:true,


idUsuario:1,


mesReferencia:
this.getCurrentMonthKey()



};



this.save();



},




getTodayISO(){


return new Date()
.toISOString()
.split("T")[0];


}



};