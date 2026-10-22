// Page d'accueil V2 : calendrier sélectionné et destinations populaires.
(function(){
 const date=document.getElementById('travel-date'),time=document.getElementById('travel-time');
 if(!date||!time)return;
 const n=new Date(Date.now()+10*60000);const z=v=>String(v).padStart(2,'0');
 date.value=n.getFullYear()+'-'+z(n.getMonth()+1)+'-'+z(n.getDate());
 time.value=z(n.getHours())+':'+z(n.getMinutes());
 window.nowLocal=function(){return date.value+'T'+time.value};
 window.selectPopular=async function(address){
   const input=document.getElementById('dropoff');input.value=address;chosen.dropoff=null;
   document.getElementById('quote').classList.remove('visible');
   if(!address){input.focus();return;}
   if(!(window.google&&google.maps&&google.maps.Geocoder)){
     toast('Destination renseignée : activez Google Maps dans /is pour la valider et calculer le prix.');input.focus();return;
   }
   try{const g=new google.maps.Geocoder();const result=await g.geocode({address:address,region:'FR'});
     const p=result.results&&result.results[0];if(!p)throw Error('Adresse introuvable');
     chosen.dropoff={lat:p.geometry.location.lat(),lng:p.geometry.location.lng(),place_id:p.place_id};input.value=p.formatted_address;
     document.getElementById('feedback').hidden=true;
   }catch(err){toast('Sélectionnez une adresse proposée par Google.',true);input.focus();}
 };
 // Une modification manuelle invalide les coordonnées précédemment choisies.
 for(const key of ['pickup','dropoff']){const el=document.getElementById(key);el.addEventListener('input',()=>{chosen[key]=null;document.getElementById('quote').classList.remove('visible')});}
})();
